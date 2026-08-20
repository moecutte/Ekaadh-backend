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

    public function __construct(private TelesomSmsService $sms) {}

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

        $this->assertDeliveryAllowed();

        Cache::put($key, [
            'hash' => hash('sha256', $code),
            'attempts' => 0,
        ], $ttl);

        // Prefer fixed/test OTP over live SMS so local/staging never hangs on Telesom.
        if (filled(config('otp.fixed_code'))) {
            Log::info('OTP issued (fixed code — SMS skipped)', [
                'purpose' => $purpose,
                'phone' => $this->redact($normalized),
                'ttl' => $ttl,
            ]);

            return [
                'phone' => $normalized,
                'message' => 'Use confirmation code '.$code.' (testing — fixed OTP).',
                'expires_in' => $ttl,
                'debug_code' => $code,
            ];
        }

        if ($this->sms->enabled()) {
            try {
                $this->sms->sendOtp($normalized, $code, $ttl);
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

            Log::info('OTP issued via Telesom', [
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

        if (app()->environment('production')) {
            Cache::forget($key);
            throw ValidationException::withMessages([
                'phone' => ['Could not send the confirmation code. Please try again.'],
            ]);
        }

        Log::info('OTP issued (stub delivery — Telesom SMS not configured)', [
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

        // Surface code only when explicitly enabled — never by APP_ENV=local alone.
        $expose = filled(config('otp.fixed_code'))
            || filter_var(config('otp.expose_debug_code'), FILTER_VALIDATE_BOOLEAN);
        if ($expose) {
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
        $this->storeVerified($purpose, $normalized, $token);

        return $token;
    }

    /**
     * Ensure the phone was verified for this purpose (otp_token from verify).
     */
    public function assertVerified(string $phone, string $purpose, ?string $otpToken): void
    {
        $this->assertPurpose($purpose);
        $normalized = $this->normalize($phone);
        $otpToken = is_string($otpToken) ? trim($otpToken) : '';

        if ($otpToken === '') {
            throw ValidationException::withMessages([
                'otp_token' => ['Confirm your phone number with the code we sent.'],
            ]);
        }

        if (! $this->verifiedTokenMatches($purpose, $normalized, $otpToken)) {
            throw ValidationException::withMessages([
                'otp_token' => ['Phone confirmation expired or invalid. Request a new code.'],
            ]);
        }
    }

    public function consumeVerified(string $phone, string $purpose, ?string $otpToken): void
    {
        $this->assertVerified($phone, $purpose, $otpToken);
        $this->forgetVerified($purpose, Phone::normalize($phone), is_string($otpToken) ? trim($otpToken) : '');
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
                        ->where(function ($q) use ($variants) {
                            $q->whereIn('buyer_phone', $variants)
                                ->orWhereHas('payment', function ($q) use ($variants) {
                                    $q->whereIn('phone_number', $variants);
                                });
                        });
                })->orWhereHas('invitation', function ($q) use ($variants) {
                    $q->where('status', 'active')
                        ->whereIn('guest_phone', $variants);
                });
            })
            ->latest()
            ->get();
    }

    private function assertDeliveryAllowed(): void
    {
        $production = app()->environment('production');
        $fixed = filled(config('otp.fixed_code'));
        $expose = filter_var(config('otp.expose_debug_code'), FILTER_VALIDATE_BOOLEAN);

        if ($production && ($fixed || $expose)) {
            throw ValidationException::withMessages([
                'phone' => ['Could not send the confirmation code. Please try again.'],
            ]);
        }
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

    private function tokenIndexKey(string $token): string
    {
        return 'otp:verified-token:'.$token;
    }

    private function sessionVerifiedKey(string $purpose, string $phone): string
    {
        return $this->verifiedKey($purpose, $phone);
    }

    private function sessionTokenIndexKey(string $token): string
    {
        return $this->tokenIndexKey($token);
    }

    private function storeVerified(string $purpose, string $phone, string $token): void
    {
        $ttl = (int) config('otp.ttl_seconds', 600);
        Cache::put($this->verifiedKey($purpose, $phone), $token, $ttl);
        Cache::put($this->tokenIndexKey($token), [
            'purpose' => $purpose,
            'phone' => $phone,
        ], $ttl);

        $this->withSession(function ($session) use ($purpose, $phone, $token) {
            $session->put($this->sessionVerifiedKey($purpose, $phone), $token);
            $session->put($this->sessionTokenIndexKey($token), [
                'purpose' => $purpose,
                'phone' => $phone,
            ]);
        });
    }

    private function forgetVerified(string $purpose, string $phone, string $token): void
    {
        Cache::forget($this->verifiedKey($purpose, $phone));
        if ($token !== '') {
            Cache::forget($this->tokenIndexKey($token));
        }

        $this->withSession(function ($session) use ($purpose, $phone, $token) {
            $session->forget($this->sessionVerifiedKey($purpose, $phone));
            if ($token !== '') {
                $session->forget($this->sessionTokenIndexKey($token));
            }
        });
    }

    private function verifiedTokenMatches(string $purpose, string $normalized, string $otpToken): bool
    {
        $expected = Cache::get($this->verifiedKey($purpose, $normalized));
        if (is_string($expected) && hash_equals($expected, $otpToken)) {
            return true;
        }

        $fromSession = null;
        $this->withSession(function ($session) use ($purpose, $normalized, &$fromSession) {
            $fromSession = $session->get($this->sessionVerifiedKey($purpose, $normalized));
        });
        if (is_string($fromSession) && hash_equals($fromSession, $otpToken)) {
            return true;
        }

        $meta = Cache::get($this->tokenIndexKey($otpToken));
        if (! is_array($meta)) {
            $this->withSession(function ($session) use ($otpToken, &$meta) {
                $stored = $session->get($this->sessionTokenIndexKey($otpToken));
                if (is_array($stored)) {
                    $meta = $stored;
                }
            });
        }

        if (! is_array($meta) || ($meta['purpose'] ?? '') !== $purpose) {
            return false;
        }

        return Phone::matches((string) ($meta['phone'] ?? ''), $normalized);
    }

    private function withSession(callable $callback): void
    {
        try {
            $request = request();
            if ($request->hasSession()) {
                $callback($request->session());
            }
        } catch (\Throwable) {
            // API / console requests may have no session.
        }
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
