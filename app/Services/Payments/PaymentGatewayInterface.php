<?php

namespace App\Services\Payments;

interface PaymentGatewayInterface
{
    /**
     * @param  array{phone?: string, force_fail?: bool, pin?: string, description?: string}  $options
     * @return array{status: string, transaction_id: string, message: string, raw: array}
     */
    public function initiate(float $amount, string $reference, array $options = []): array;

    /**
     * Look up an existing charge by merchant reference (and optional provider transaction id).
     *
     * @return array{status: string, transaction_id: string, message: string, raw: array}
     */
    public function inquire(string $reference, ?string $transactionId = null): array;

    public function name(): string;
}
