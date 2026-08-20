<?php

namespace App\Services\Payments;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class WaafiPayClient
{
    public function purchaseEnabled(): bool
    {
        return filled(config('waafipay.merchant_uid'))
            && filled(config('waafipay.api_user_id'))
            && filled(config('waafipay.api_key'));
    }

    /**
     * @param  array<string, mixed>  $serviceParams
     * @return array<string, mixed>
     */
    public function call(string $serviceName, array $serviceParams): array
    {
        if (isset($serviceParams['payerInfo']) && is_array($serviceParams['payerInfo'])) {
            $accountNo = preg_replace('/\D+/', '', (string) ($serviceParams['payerInfo']['accountNo'] ?? '')) ?? '';
            $serviceParams['payerInfo']['accountNo'] = $accountNo;
        }

        $payload = [
            'schemaVersion' => '1.0',
            'requestId' => (string) Str::uuid(),
            'timestamp' => now()->format('Y-m-d H:i:s.v'),
            'channelName' => (string) config('waafipay.channel', 'WEB'),
            'serviceName' => $serviceName,
            'serviceParams' => $serviceParams,
        ];

        $request = Http::timeout((int) config('waafipay.timeout', 45))
            ->acceptJson()
            ->asJson();

        $cafile = (string) config('waafipay.cafile', '');
        if ($cafile !== '' && is_file($cafile)) {
            $request = $request->withOptions(['verify' => $cafile]);
        }

        try {
            $response = $request->post((string) config('waafipay.base_url'), $payload);
        } catch (ConnectionException $e) {
            $ssl = str_contains(strtolower($e->getMessage()), 'ssl')
                || str_contains($e->getMessage(), 'certificate')
                || str_contains($e->getMessage(), 'cURL error 60');

            Log::warning($ssl ? 'WaafiPay SSL verification failed' : 'WaafiPay request timed out', [
                'service' => $serviceName,
                'error' => $e->getMessage(),
            ]);

            throw new RuntimeException(
                $ssl
                    ? 'Could not connect to WaafiPay because PHP could not verify the SSL certificate. Set WAAFIPAY_CAFILE to a CA bundle (XAMPP: C:\\xampp\\apache\\bin\\curl-ca-bundle.crt).'
                    : 'WaafiPay request timed out.',
                0,
                $e
            );
        }

        $json = $response->json();
        if (! is_array($json)) {
            Log::error('WaafiPay unexpected response', [
                'service' => $serviceName,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new RuntimeException('WaafiPay returned a non-JSON response.');
        }

        if (! $response->successful()) {
            Log::error('WaafiPay HTTP error', [
                'service' => $serviceName,
                'status' => $response->status(),
                'body' => $json,
            ]);
        }

        $params = is_array($json['params'] ?? null) ? $json['params'] : [];

        Log::info('WaafiPay response', [
            'service' => $serviceName,
            'http_status' => $response->status(),
            'response_code' => $json['responseCode'] ?? null,
            'error_code' => $json['errorCode'] ?? null,
            'response_msg' => $json['responseMsg'] ?? null,
            'state' => $params['state'] ?? null,
            'detail' => $params['description'] ?? null,
        ]);

        return $json;
    }
}
