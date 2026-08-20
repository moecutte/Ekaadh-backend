<?php

namespace App\Support;

class Phone
{
    /**
     * Normalize Somali mobiles to +252XXXXXXXXX (digits only after country code).
     */
    public static function normalize(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone) ?? '';

        if ($digits === '') {
            return '';
        }

        if (str_starts_with($digits, '252')) {
            $digits = substr($digits, 3);
        }

        $digits = ltrim($digits, '0');

        return $digits === '' ? '' : '+252'.$digits;
    }

    /**
     * Digits-only international number for WaafiPay (no +, no leading zeros).
     * Example: 252611111111 — never +252611111111 or 0611111111.
     */
    public static function internationalDigits(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', str_replace('+', '', (string) $phone)) ?? '';

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        $digits = ltrim($digits, '0');
        if ($digits === '') {
            return '';
        }

        foreach (['252' => 12, '253' => 11, '971' => 12] as $cc => $minLen) {
            if (str_starts_with($digits, $cc) && strlen($digits) >= $minLen) {
                return $digits;
            }
        }

        return '252'.$digits;
    }

    public static function variants(?string $phone): array
    {
        $normalized = self::normalize($phone);
        if ($normalized === '') {
            return array_values(array_filter([(string) $phone]));
        }

        $local = substr($normalized, 4); // after +252

        return array_values(array_unique(array_filter([
            $normalized,
            '252'.$local,
            '0'.$local,
            $local,
            '+252 '.$local,
            (string) $phone,
        ])));
    }

    public static function matches(?string $stored, ?string $input): bool
    {
        if ($stored === null || $stored === '' || $input === null || $input === '') {
            return false;
        }

        $want = self::variants($input);

        return count(array_intersect(self::variants($stored), $want)) > 0;
    }
}
