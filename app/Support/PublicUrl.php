<?php

namespace App\Support;

class PublicUrl
{
    /**
     * Build a public link from APP_URL, not the current request host.
     * SMS/WhatsApp/email must not use localhost or an internal Coolify hostname.
     */
    public static function to(string $path): string
    {
        $root = rtrim((string) config('app.url'), '/');
        $path = '/'.ltrim(str_replace('\\', '/', $path), '/');

        return $root !== '' ? $root.$path : url($path);
    }
}
