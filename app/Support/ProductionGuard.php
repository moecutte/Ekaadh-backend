<?php

namespace App\Support;

use Illuminate\Support\Facades\URL;
use RuntimeException;

class ProductionGuard
{
    public static function assert(): void
    {
        if (! app()->environment('production') || app()->runningUnitTests()) {
            return;
        }

        $errors = [];

        if ((bool) config('app.debug')) {
            $errors[] = 'APP_DEBUG must be false in production.';
        }

        if (filled(config('otp.fixed_code'))) {
            $errors[] = 'OTP_FIXED_CODE must be empty in production.';
        }

        if (filter_var(config('otp.expose_debug_code'), FILTER_VALIDATE_BOOLEAN)) {
            $errors[] = 'OTP_EXPOSE_DEBUG_CODE must be false in production.';
        }

        if ((bool) config('waafipay.sandbox')) {
            $errors[] = 'WAAFIPAY_MODE must be live in production.';
        }

        if ((string) config('services.payment.gateway', 'waafipay') === 'mock') {
            $errors[] = 'PAYMENT_GATEWAY=mock is not allowed in production.';
        }

        $origins = config('cors.allowed_origins', []);
        if (! is_array($origins) || $origins === [] || in_array('*', $origins, true)) {
            $errors[] = 'CORS_ALLOWED_ORIGINS must list real origins in production (not *).';
        }

        if (trim((string) config('services.ticket_qr.secret')) === '') {
            $errors[] = 'TICKET_QR_SECRET must be set in production so QR HMACs survive APP_KEY rotation.';
        }

        if (! (bool) config('session.encrypt')) {
            $errors[] = 'SESSION_ENCRYPT must be true in production.';
        }

        if ($errors !== []) {
            throw new RuntimeException('Production safety: '.implode(' ', $errors));
        }

        URL::forceScheme('https');
    }
}
