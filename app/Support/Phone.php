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
}
