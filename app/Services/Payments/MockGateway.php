<?php

namespace App\Services\Payments;

class MockGateway implements PaymentGatewayInterface
{
    public function name(): string
    {
        return 'mock';
    }

    public function initiate(float $amount, string $reference, array $options = []): array
    {
        $forceFail = (bool) ($options['force_fail'] ?? false);
        $transactionId = 'MOCK-'.strtoupper(bin2hex(random_bytes(4)));

        if ($forceFail) {
            return [
                'status' => 'failed',
                'transaction_id' => $transactionId,
                'message' => 'Mock payment declined. Insufficient wallet balance.',
                'raw' => [
                    'provider' => 'mock',
                    'reference' => $reference,
                    'amount' => $amount,
                    'phone' => $options['phone'] ?? null,
                    'result' => 'DECLINED',
                ],
            ];
        }

        return [
            'status' => 'success',
            'transaction_id' => $transactionId,
            'message' => 'Mock payment successful.',
            'raw' => [
                'provider' => 'mock',
                'reference' => $reference,
                'amount' => $amount,
                'phone' => $options['phone'] ?? null,
                'result' => 'APPROVED',
            ],
        ];
    }

    public function inquire(string $reference, ?string $transactionId = null): array
    {
        return [
            'status' => 'unknown',
            'transaction_id' => $transactionId ?: $reference,
            'message' => 'Mock gateway has no remote transaction to look up.',
            'raw' => [
                'provider' => 'mock',
                'reference' => $reference,
                'result' => 'NOT_SUPPORTED',
            ],
        ];
    }
}
