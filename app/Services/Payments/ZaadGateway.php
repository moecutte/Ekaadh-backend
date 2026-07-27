<?php

namespace App\Services\Payments;

/**
 * Stub for Zaad (Telesom) wallet API.
 * Wire credentials via ZAAD_* env vars when merchant access is approved.
 */
class ZaadGateway implements PaymentGatewayInterface
{
    public function name(): string
    {
        return 'zaad';
    }

    public function initiate(float $amount, string $reference, array $options = []): array
    {
        if (! config('services.zaad.enabled')) {
            return [
                'status' => 'failed',
                'transaction_id' => 'ZAAD-UNCONFIGURED',
                'message' => 'Zaad gateway is not configured yet. Set ZAAD_ENABLED=true and API credentials.',
                'raw' => [
                    'provider' => 'zaad',
                    'reference' => $reference,
                    'amount' => $amount,
                    'result' => 'NOT_CONFIGURED',
                ],
            ];
        }

        // TODO: call Zaad merchant API when credentials arrive.
        return [
            'status' => 'failed',
            'transaction_id' => 'ZAAD-PENDING-INTEGRATION',
            'message' => 'Zaad API integration pending.',
            'raw' => [
                'provider' => 'zaad',
                'reference' => $reference,
                'amount' => $amount,
                'result' => 'PENDING_INTEGRATION',
            ],
        ];
    }
}
