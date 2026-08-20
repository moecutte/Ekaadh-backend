<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public const SUPPORTED = ['en', 'so'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = session('locale');
        if (! is_string($locale) || $locale === '') {
            $header = strtolower(trim((string) $request->header('Accept-Language', '')));
            $locale = str_starts_with($header, 'so') ? 'so' : (string) config('app.locale', 'en');
        }

        if (! in_array($locale, self::SUPPORTED, true)) {
            $locale = 'en';
        }

        App::setLocale($locale);

        return $next($request);
    }
}
