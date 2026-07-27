<?php

namespace App\Services\Payments;

interface PaymentGatewayInterface
{
    /**
     * @param  array{phone?: string, force_fail?: bool}  $options
     * @return array{status: string, transaction_id: string, message: string, raw: array}
     */
    public function initiate(float $amount, string $reference, array $options = []): array;

    public function name(): string;
}
