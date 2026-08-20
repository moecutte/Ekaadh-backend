<?php

namespace App\Console\Commands;

use App\Services\TelesomSmsService;
use App\Support\Phone;
use Illuminate\Console\Command;
use Throwable;

class TelesomTestCommand extends Command
{
    protected $signature = 'telesom:test
                            {phone : Recipient mobile (e.g. 0631234567)}
                            {--otp : Send a verification-code SMS (standard prepaid until OTP product is enabled)}';

    protected $description = 'Send a test SMS through the Telesom prepaid gateway to verify credentials';

    public function handle(TelesomSmsService $sms): int
    {
        $phone = Phone::normalize((string) $this->argument('phone'));
        if ($phone === '') {
            $this->error('Invalid phone number.');

            return self::FAILURE;
        }

        if (! $sms->enabled()) {
            $this->error('Telesom SMS is not configured.');
            $this->line('Set TELESOM_SENDER_ID, TELESOM_USERNAME, TELESOM_PASSWORD, and TELESOM_SECRET_KEY in .env');

            return self::FAILURE;
        }

        $otp = (bool) $this->option('otp');
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $ttl = (int) config('otp.ttl_seconds', 600);

        $this->info($otp ? "Sending verification SMS to {$phone}…" : "Sending SMS to {$phone}…");

        try {
            $result = $otp
                ? $sms->sendOtp($phone, $code, $ttl)
                : $sms->send($phone, 'Ekaadh Telesom SMS test');
        } catch (Throwable $e) {
            $this->error($e->getMessage());
            $this->line('Check laravel.log for the Telesom API response.');

            return self::FAILURE;
        }

        $requestId = data_get($result, 'request_id', 'unknown');
        $messageId = data_get($result, 'results.0.message_id', 'unknown');
        $this->info("Accepted by Telesom. request_id={$requestId} message_id={$messageId}");

        if ($otp) {
            $this->line("Verification code sent: {$code}");
        }

        return self::SUCCESS;
    }
}
