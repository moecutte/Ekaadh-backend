<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\User;
use App\Support\Phone;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class OtpService
{
    public const PURPOSE_REGISTER = 'register';

    public const PURPOSE_CHECKOUT = 'checkout';

    public const PURPOSE_FIND_TICKETS = 'find_tickets';

    public function __construct(private TextBeeSmsService $sms) {}

    public function normalize(string $phone): string
    {
        $normalized = Phone::normalize($phone);
        if ($normalized === '' || strlen($normalized) < 12) {
            throw ValidationException::withMessages([
                'phone' => ['Enter a valid phone number.'],
            ]);
        }

        return $normalized;
    }

    /**
     * @return array{phone: string, message: string, expires_in: int, debug_code?: string}
     */
    public function send(string $phone, string $purpose): array
    {
        $this->assertPurpose($purpose);
        $normalized = $this->normalize($phone);

        match ($purpose) {
            self::PURPOSE_REGISTER => $this->assertPhoneAvailableForRegister($normalized),
            self::PURPOSE_CHECKOUT => $this->assertGuestPhoneNotRegistered($normalized),
            self::PURPOSE_FIND_TICKETS => $this->assertHasFindableTickets($normalized),
            default => null,
        };

        $code = $this->generateCode();
        $ttl = (int) config('otp.ttl_seconds', 600);
        $key = $this->cacheKey($purpose, $normalized);

        Cache::put($key, [
            'hash' => hash('sha256', $code),
            'attempts' => 0,
        ], $ttl);

        $minutes = max(1, (int) ceil($ttl / 60));
        $smsBody = str_replace(
            [':code', ':minutes'],
            [$code, (string) $minutes],
            (string) config('otp.sms_message', 'Your Ekaadh code is :code. Valid for :minutes minutes.')
        );

        if ($this->sms->enabled()) {
            try {
                $this->sms->send($normalized, $smsBody);
            } catch (\Throwable $e) {
                Cache::forget($key);
                Log::error('OTP SMS delivery failed', [
                    'purpose' => $purpose,
                    'phone' => $this->redact($normalized),
                    'error' => $e->getMessage(),
                ]);

                throw ValidationException::withMessages([
                    'phone' => ['Could not send the confirmation code. Please try again.'],
                ]);
            }

            Log::info('OTP issued via TextBee', [
                'purpose' => $purpose,
                'phone' => $this->redact($normalized),
                'ttl' => $ttl,
            ]);

            return [
                'phone' => $normalized,
                'message' => 'A confirmation code was sent to your phone.',
                'expires_in' => $ttl,
            ];
        }

        Log::info('OTP issued (stub delivery — TextBee not configured)', [
            'purpose' => $purpose,
            'phone' => $this->redact($normalized),
            'code' => $code,
            'ttl' => $ttl,
        ]);

        $payload = [
            'phone' => $normalized,
            'message' => 'A confirmation code was sent to your phone.',
            'expires_in' => $ttl,
        ];

        // Surface code only when SMS is not configured (local/dev).
        if (config('otp.fixed_code') || app()->environment('local')) {
            $payload['debug_code'] = $code;
            $payload['message'] = 'Use confirmation code '.$code.' (testing — SMS not configured).';
        }

        return $payload;
    }

    public function verify(string $phone, string $purpose, string $code): string
    {
        $this->assertPurpose($purpose);
        $normalized = $this->normalize($phone);
        $code = trim($code);

        if ($code === '') {
            throw ValidationException::withMessages([
                'otp' => ['Enter the confirmation code.'],
            ]);
        }

        $fixed = (string) config('otp.fixed_code', '');
        $key = $this->cacheKey($purpose, $normalized);
        $stored = Cache::get($key);

        $accepted = false;
        if ($fixed !== '' && hash_equals($fixed, $code)) {
            $accepted = true;
        } elseif (is_array($stored) && isset($stored['hash'])) {
            $attempts = (int) ($stored['attempts'] ?? 0);
            if ($attempts >= 8) {
                Cache::forget($key);
                throw ValidationException::withMessages([
                    'otp' => ['Too many attempts. Request a new code.'],
                ]);
            }
            Cache::put($key, array_merge($stored, ['attempts' => $attempts + 1]), (int) config('otp.ttl_seconds', 600));
            $accepted = hash_equals($stored['hash'], hash('sha256', $code));
        }

        if (! $accepted) {
            throw ValidationException::withMessages([
                'otp' => ['Invalid or expired confirmation code.'],
            ]);
        }

        Cache::forget($key);

        $token = bin2hex(random_bytes(24));
        Cache::put($this->verifiedKey($purpose, $normalized), $token, (int) config('otp.ttl_seconds', 600));

        return $token;
    }

    /**
     * Ensure the phone was verified for this purpose (otp_token from verify).
     */
    public function assertVerified(string $phone, string $purpose, ?string $otpToken): void
    {
        $this->assertPurpose($purpose);
        $normalized = $this->normalize($phone);

        if (! $otpToken) {
            throw ValidationException::withMessages([
                'otp_token' => ['Confirm your phone number with the code we sent.'],
            ]);
        }

        $expected = Cache::get($this->verifiedKey($purpose, $normalized));
        if (! is_string($expected) || ! hash_equals($expected, $otpToken)) {
            throw ValidationException::withMessages([
                'otp_token' => ['Phone confirmation expired or invalid. Request a new code.'],
            ]);
        }
    }

    public function consumeVerified(string $phone, string $purpose, ?string $otpToken): void
    {
        $this->assertVerified($phone, $purpose, $otpToken);
        Cache::forget($this->verifiedKey($purpose, Phone::normalize($phone)));
    }

    public function guestPhoneIsRegistered(string $phone): bool
    {
        $variants = Phone::variants($phone);

        return User::query()
            ->where('role', User::ROLE_CUSTOMER)
            ->whereIn('phone', $variants)
            ->exists();
    }

    /**
     * Valid (unused) tickets for guest find-by-phone.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Ticket>
     */
    public function findableTicketsForPhone(string $phone)
    {
        $variants = Phone::variants($phone);

        return Ticket::query()
            ->with(['event', 'orderItem.order', 'invitation'])
            ->where('status', 'valid')
            ->where(function ($q) use ($variants) {
                $q->whereHas('orderItem.order', function ($q) use ($variants) {
                    $q->where('status', 'paid')
                        ->whereIn('buyer_phone', $variants);
                })->orWhereHas('invitation', function ($q) use ($variants) {
                    $q->where('status', 'active')
                        ->whereIn('guest_phone', $variants);
                });
            })
            ->where(function ($q) {
                $q->whereHas('event', function ($e) {
                    $e->whereNull('event_date')
                        ->orWhereDate('event_date', '>=', now()->toDateString());
                });
            })
            ->latest()
            ->get();
    }

    private function assertPhoneAvailableForRegister(string $normalized): void
    {
        if ($this->guestPhoneIsRegistered($normalized)) {
            throw ValidationException::withMessages([
                'phone' => ['This phone number already has an account. Please sign in.'],
            ]);
        }
    }

    private function assertGuestPhoneNotRegistered(string $normalized): void
    {
        if ($this->guestPhoneIsRegistered($normalized)) {
            throw ValidationException::withMessages([
                'phone' => ['This phone number belongs to a customer account. Please sign in to continue.'],
            ]);
        }
    }

    private function assertHasFindableTickets(string $normalized): void
    {
        if ($this->findableTicketsForPhone($normalized)->isEmpty()) {
            throw ValidationException::withMessages([
                'phone' => ['No available valid tickets found for this phone number.'],
            ]);
        }
    }

    private function generateCode(): string
    {
        $fixed = (string) config('otp.fixed_code', '');
        if ($fixed !== '') {
            return $fixed;
        }

        $length = max(4, (int) config('otp.length', 6));
        $max = (10 ** $length) - 1;

        return str_pad((string) random_int(0, $max), $length, '0', STR_PAD_LEFT);
    }

    private function assertPurpose(string $purpose): void
    {
        $allowed = config('otp.purposes', [
            self::PURPOSE_REGISTER,
            self::PURPOSE_CHECKOUT,
            self::PURPOSE_FIND_TICKETS,
        ]);

        if (! in_array($purpose, $allowed, true)) {
            throw ValidationException::withMessages([
                'purpose' => ['Invalid OTP purpose.'],
            ]);
        }
    }

    private function cacheKey(string $purpose, string $phone): string
    {
        return 'otp:pending:'.$purpose.':'.$phone;
    }

    private function verifiedKey(string $purpose, string $phone): string
    {
        return 'otp:verified:'.$purpose.':'.$phone;
    }

    private function redact(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if (strlen($digits) < 4) {
            return '***';
        }

        return str_repeat('*', max(0, strlen($digits) - 4)).substr($digits, -4);
    }
}
