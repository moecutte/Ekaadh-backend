<?php

namespace App\Services\Payments;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class EDahabClient
{
    public function configured(): bool
    {
        return filled(config('edahab.api_key'))
            && filled(config('edahab.api_secret'))
            && filled(config('edahab.agent_code'));
    }

    /**
     * SHA-256 hex of the exact JSON body with the API secret appended.
     *
     * @see https://docs.edahab.net/api-introduction/
     */
    public static function hash(string $body, string $secret): string
    {
        return hash('sha256', $body.$secret);
    }

    /**
     * National eDahab wallet number (no country code). +25265… becomes 65….
     */
    public static function walletNumber(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone) ?? '';
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }
        if (str_starts_with($digits, '252')) {
            $digits = substr($digits, 3);
        }
        $digits = ltrim($digits, '0');
        if (strlen($digits) < 7 || strlen($digits) > 15) {
            return '';
        }

        return $digits;
    }

    public static function amount(float $amount): int|float
    {
        $rounded = round($amount, 2);
        $whole = round($rounded);
        if (abs($rounded - $whole) < 0.001) {
            return (int) $whole;
        }

        return $rounded;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function post(string $endpoint, array $payload): array
    {
        $body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if (! is_string($body) || $body === '') {
            throw new RuntimeException('Could not encode the eDahab request.');
        }

        $hash = self::hash($body, (string) config('edahab.api_secret'));
        $url = rtrim((string) config('edahab.base_url'), '/').'/'.$endpoint.'?hash='.$hash;

        $request = Http::timeout((int) config('edahab.timeout', 120))
            ->acceptJson()
            ->withBody($body, 'application/json');

        $cafile = (string) config('edahab.cafile', '');
        if ($cafile !== '' && is_file($cafile)) {
            $request = $request->withOptions(['verify' => $cafile]);
        }

        try {
            $response = $request->post($url);
        } catch (ConnectionException $e) {
            Log::warning('eDahab request failed to connect', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);

            throw new RuntimeException('eDahab request failed to connect.', 0, $e);
        }

        $json = $response->json();
        if (! is_array($json)) {
            Log::warning('eDahab returned a non-JSON response', [
                'endpoint' => $endpoint,
                'status' => $response->status(),
            ]);

            throw new RuntimeException('eDahab returned a non-JSON response.');
        }

        Log::info('eDahab response', [
            'endpoint' => $endpoint,
            'http_status' => $response->status(),
            'status_code' => $json['StatusCode'] ?? $json['statusCode'] ?? null,
            'invoice_status' => $json['InvoiceStatus'] ?? $json['invoiceStatus'] ?? null,
        ]);

        return $json;
    }
}
