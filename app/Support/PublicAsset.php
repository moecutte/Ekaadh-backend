<?php

namespace App\Support;

class PublicAsset
{
    /**
     * Turn a stored relative path or absolute URL into a URL for this app.
     */
    public static function url(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            $path = parse_url($value, PHP_URL_PATH);
            if (is_string($path) && $path !== '' && self::isAppPublicPath($path)) {
                return asset(ltrim($path, '/'));
            }

            return $value;
        }

        return asset(ltrim($value, '/'));
    }

    private static function isAppPublicPath(string $path): bool
    {
        $path = '/'.ltrim($path, '/');

        return str_starts_with($path, '/storage/')
            || str_starts_with($path, '/images/');
    }
}
