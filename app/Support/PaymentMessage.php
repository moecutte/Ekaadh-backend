<?php

namespace App\Support;

class PaymentMessage
{
    public static function forOrder(?object $order, ?string $fallback = null): string
    {
        try {
            $raw = $order?->payment?->raw_response ?? null;
        } catch (\Throwable) {
            $raw = null;
        }

        return self::forFailure(is_array($raw) ? $raw : null, $fallback);
    }

    public static function forFailure(?array $raw, ?string $fallback = null): string
    {
        $raw = $raw ?? [];
        $stored = trim((string) ($raw['user_message'] ?? ''));
        $errorCode = (string) ($raw['error_code'] ?? $raw['response_code'] ?? '');
        if ($stored !== '' && self::isFriendly($stored) && ! in_array($errorCode, ['1001', '5001', '2007'], true)) {
            return $stored;
        }

        $response = is_array($raw['response'] ?? null) ? $raw['response'] : [];
        $params = is_array($response['params'] ?? null) ? $response['params'] : [];
        $responseMsg = (string) ($response['responseMsg'] ?? $raw['result'] ?? $raw['error'] ?? $stored);
        $detail = (string) ($params['description'] ?? '');
        $errorCode = (string) (
            $raw['error_code']
            ?? $response['errorCode']
            ?? $raw['response_code']
            ?? $response['responseCode']
            ?? ''
        );
        $state = (string) ($raw['result'] ?? '');

        return self::fromProvider($responseMsg.' '.$detail, $errorCode, $state, $fallback);
    }

    public static function fromProvider(string $responseMsg, string $errorCode = '', string $state = '', ?string $fallback = null): string
    {
        $haystack = strtoupper($responseMsg.' '.$errorCode.' '.$state);

        if (self::containsAny($haystack, ['INSUFFICIENT', 'BALANCE', 'E10205', '5206'])) {
            return __('ui.payment_failed_insufficient');
        }

        if (self::containsAny($haystack, ['USER_REJECTED', 'CANCEL', '5310', 'DECLINED'])) {
            return __('ui.payment_failed_cancelled');
        }

        if (self::containsAny($haystack, ['INVALID_PHONE', 'INVALID PHONE', 'ACCOUNTNO', 'WAAFI-INVALID', 'INVALID ACCOUNT'])) {
            return __('ui.payment_failed_invalid_phone');
        }

        if (self::containsAny($haystack, ['2007', 'SANDBOX TEST ACCOUNT', 'TEST ACCOUNTS'])) {
            return __('ui.payment_failed_sandbox_wallet');
        }

        if (self::containsAny($haystack, ['SANDBOX_KEYS_MISSING'])) {
            return __('ui.payment_failed_sandbox_credentials');
        }

        if (self::containsAny($haystack, ['AUTHENTICATION', '1001', '5001'])) {
            return __('ui.payment_failed_auth');
        }

        if (self::containsAny($haystack, ['TIMEOUT', 'SSL', 'CERTIFICATE', 'CURL', 'NOT_CONFIGURED'])) {
            return __('ui.payment_failed_unavailable');
        }

        if (self::containsAny($haystack, ['COULD NOT BE PROCESSED', 'ERROR DETAILS'])) {
            return __('ui.payment_failed_unavailable');
        }

        $clean = trim($responseMsg);
        if ($clean !== '' && self::isFriendly($clean)) {
            return $clean;
        }

        return $fallback ?: __('ui.payment_failed_desc');
    }

    public static function isFriendly(string $text): bool
    {
        $lower = strtolower($text);

        if ($text === '') {
            return false;
        }

        return ! str_contains($lower, 'curl')
            && ! str_contains($lower, 'ssl')
            && ! str_contains($lower, 'certificate')
            && ! str_contains($lower, 'cafile')
            && ! str_contains($lower, 'sqlstate')
            && ! str_contains($lower, 'stack trace')
            && ! str_contains($lower, 'error details')
            && ! str_contains($lower, 'could not be processed')
            && ! str_contains($lower, 'sandbox test')
            && ! str_starts_with(strtoupper($text), 'RCS_')
            && ! preg_match('/your balance is\s*\)/i', $text);
    }

    /**
     * @param  list<string>  $needles
     */
    private static function containsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }
}
