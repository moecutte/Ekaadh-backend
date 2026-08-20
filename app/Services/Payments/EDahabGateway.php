<?php

namespace App\Services\Payments;

/**
 * Stub for eDahab (Somtel) wallet API.
 * Wire credentials via EDAHAB_* env vars when merchant access is approved.
 */
class EDahabGateway implements PaymentGatewayInterface
{
    public function name(): string
    {
        return 'edahab';
    }

    public function initiate(float $amount, string $reference, array $options = []): array
    {
        if (! config('services.edahab.enabled')) {
            return [
                'status' => 'failed',
                'transaction_id' => 'EDAHAB-UNCONFIGURED',
                'message' => 'eDahab gateway is not configured yet. Set EDAHAB_ENABLED=true and API credentials.',
                'raw' => [
                    'provider' => 'edahab',
                    'reference' => $reference,
                    'amount' => $amount,
                    'result' => 'NOT_CONFIGURED',
                ],
            ];
        }

        // TODO: call eDahab merchant API when credentials arrive.
        return [
            'status' => 'failed',
            'transaction_id' => 'EDAHAB-PENDING-INTEGRATION',
            'message' => 'eDahab API integration pending.',
            'raw' => [
                'provider' => 'edahab',
                'reference' => $reference,
                'amount' => $amount,
                'result' => 'PENDING_INTEGRATION',
            ],
        ];
    }

    public function inquire(string $reference, ?string $transactionId = null): array
    {
        return [
            'status' => 'unknown',
            'transaction_id' => $transactionId ?: $reference,
            'message' => 'eDahab inquiry is not available yet.',
            'raw' => [
                'provider' => 'edahab',
                'reference' => $reference,
                'result' => 'NOT_SUPPORTED',
            ],
        ];
    }
}
