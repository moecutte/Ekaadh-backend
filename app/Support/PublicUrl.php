<?php

namespace App\Support;

class PublicUrl
{
    /**
     * Build a public link from APP_PUBLIC_URL / APP_URL, not the current request host.
     * SMS/WhatsApp/email must not use localhost or an internal Coolify hostname.
     */
    public static function to(string $path): string
    {
        $root = rtrim((string) (config('app.public_url') ?: config('app.url')), '/');
        $path = '/'.ltrim(str_replace('\\', '/', $path), '/');

        if ($root === '') {
            return url($path);
        }

        $parts = parse_url($root) ?: [];
        $host = strtolower((string) ($parts['host'] ?? ''));
        if ($host === '') {
            return rtrim($root, '/').$path;
        }
        $scheme = strtolower((string) ($parts['scheme'] ?? 'https'));

        if (app()->environment('production') || self::looksPublicHost($host)) {
            $scheme = 'https';
        }

        if (self::looksInternalHost($host) && app()->environment('production')) {
            $fallback = rtrim((string) config('app.url'), '/');
            $fallbackHost = strtolower((string) (parse_url($fallback, PHP_URL_HOST) ?: ''));
            if ($fallback !== '' && ! self::looksInternalHost($fallbackHost)) {
                $root = $fallback;
                $parts = parse_url($root) ?: [];
                $host = strtolower((string) ($parts['host'] ?? ''));
                $scheme = 'https';
            }
        }

        $port = $parts['port'] ?? null;
        $portSuffix = $port && ! in_array((int) $port, [80, 443], true) ? ':'.$port : '';
        $basePath = rtrim((string) ($parts['path'] ?? ''), '/');

        return $scheme.'://'.$host.$portSuffix.$basePath.$path;
    }

    private static function looksInternalHost(string $host): bool
    {
        if ($host === '' || $host === 'localhost' || str_ends_with($host, '.localhost')) {
            return true;
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return ! filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
        }

        return str_contains($host, '.coolify.')
            || str_ends_with($host, '.internal')
            || ! str_contains($host, '.');
    }

    private static function looksPublicHost(string $host): bool
    {
        return $host !== '' && ! self::looksInternalHost($host);
    }
}
