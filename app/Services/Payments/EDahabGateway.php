<?php

namespace App\Services\Payments;

use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * eDahab push/pop-up purchase.
 *
 * @see https://docs.edahab.net/purchase/
 * @see https://docs.edahab.net/check-invoice/
 */
class EDahabGateway implements PaymentGatewayInterface
{
    public function name(): string
    {
        return 'edahab';
    }

    public function initiate(float $amount, string $reference, array $options = []): array
    {
        if ((bool) ($options['force_fail'] ?? false)) {
            return $this->result('failed', 'EDAHAB-FORCE-FAIL', __('ui.payment_failed_insufficient'), [
                'provider' => 'edahab',
                'reference' => $reference,
                'amount' => $amount,
                'result' => 'DECLINED',
            ]);
        }

        $client = app(EDahabClient::class);
        if (! $client->configured()) {
            return $this->result('failed', 'EDAHAB-UNCONFIGURED', __('ui.payment_failed_unavailable'), [
                'provider' => 'edahab',
                'reference' => $reference,
                'amount' => $amount,
                'result' => 'NOT_CONFIGURED',
            ]);
        }

        $number = EDahabClient::walletNumber($options['phone'] ?? null);
        if ($number === '') {
            return $this->result('failed', 'EDAHAB-INVALID-PHONE', __('ui.payment_failed_invalid_phone'), [
                'provider' => 'edahab',
                'reference' => $reference,
                'amount' => $amount,
                'result' => 'INVALID_PHONE',
            ]);
        }

        try {
            $json = $client->post('Issueinvoice', [
                'apiKey' => (string) config('edahab.api_key'),
                'edahabNumber' => $number,
                'amount' => EDahabClient::amount($amount),
                'agentCode' => (string) config('edahab.agent_code'),
                'currency' => (string) config('edahab.currency', 'USD'),
            ]);
        } catch (Throwable $e) {
            Log::warning('eDahab Issueinvoice failed', [
                'reference' => $reference,
                'error' => $e->getMessage(),
            ]);

            return $this->result('failed', 'EDAHAB-'.$reference, __('ui.payment_failed_unavailable'), [
                'provider' => 'edahab',
                'reference' => $reference,
                'amount' => $amount,
                'result' => 'CONNECT_ERROR',
            ]);
        }

        return $this->interpret($json, $reference, $amount);
    }

    public function inquire(string $reference, ?string $transactionId = null): array
    {
        $client = app(EDahabClient::class);
        $invoiceId = trim((string) $transactionId);
        if (! $client->configured() || $invoiceId === '' || $invoiceId === $reference) {
            return $this->result('unknown', $invoiceId !== '' ? $invoiceId : $reference, __('ui.payment_confirming'), [
                'provider' => 'edahab',
                'reference' => $reference,
                'result' => 'NOT_INQUIRED',
            ]);
        }

        try {
            $json = $client->post('CheckInvoiceStatus', [
                'apiKey' => (string) config('edahab.api_key'),
                'invoiceId' => $invoiceId,
            ]);
        } catch (Throwable $e) {
            Log::warning('eDahab CheckInvoiceStatus failed', [
                'reference' => $reference,
                'error' => $e->getMessage(),
            ]);

            return $this->result('unknown', $invoiceId, __('ui.payment_confirming'), [
                'provider' => 'edahab',
                'reference' => $reference,
                'invoice_id' => $invoiceId,
                'result' => 'CONNECT_ERROR',
            ]);
        }

        return $this->interpret($json, $reference, null, $invoiceId);
    }

    /**
     * @param  array<string, mixed>  $json
     * @return array{status: string, transaction_id: string, message: string, raw: array}
     */
    private function interpret(array $json, string $reference, ?float $amount, ?string $fallbackInvoiceId = null): array
    {
        $invoiceStatus = strtolower((string) $this->value($json, 'InvoiceStatus'));
        $invoiceId = (string) ($this->value($json, 'invoiceId') ?: $fallbackInvoiceId ?: '');
        $transactionId = (string) ($this->value($json, 'TransactionId') ?: '');
        $statusCode = (int) $this->value($json, 'StatusCode');
        $description = trim((string) $this->value($json, 'StatusDescription'));

        $raw = [
            'provider' => 'edahab',
            'reference' => $reference,
            'amount' => $amount,
            'invoice_id' => $invoiceId,
            'invoice_status' => $invoiceStatus,
            'status_code' => $statusCode,
            'result' => $description !== '' ? $description : strtoupper($invoiceStatus ?: 'UNKNOWN'),
            'response' => $json,
        ];

        if ($invoiceStatus === 'paid') {
            return $this->result(
                'success',
                $transactionId !== '' ? $transactionId : ($invoiceId !== '' ? $invoiceId : $reference),
                __('ui.payment_successful'),
                $raw
            );
        }

        if ($invoiceStatus === 'pending') {
            return $this->result(
                'pending',
                $invoiceId !== '' ? $invoiceId : ($transactionId !== '' ? $transactionId : $reference),
                __('ui.payment_confirming'),
                $raw
            );
        }

        return $this->result('failed', $invoiceId !== '' ? $invoiceId : 'EDAHAB-FAILED', $this->failureMessage($statusCode, $invoiceStatus), $raw);
    }

    private function failureMessage(int $statusCode, string $invoiceStatus): string
    {
        return match ($statusCode) {
            5 => __('ui.payment_failed_insufficient'),
            3 => __('ui.payment_failed_invalid_phone'),
            default => match ($invoiceStatus) {
                'unpaid', 'invalid' => __('ui.payment_failed_cancelled'),
                default => __('ui.payment_failed_desc'),
            },
        };
    }

    private function value(array $json, string $key): mixed
    {
        if (array_key_exists($key, $json)) {
            return $json[$key];
        }

        foreach ($json as $name => $value) {
            if (strcasecmp((string) $name, $key) === 0) {
                return $value;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $raw
     * @return array{status: string, transaction_id: string, message: string, raw: array}
     */
    private function result(string $status, string $transactionId, string $message, array $raw): array
    {
        return [
            'status' => $status,
            'transaction_id' => $transactionId,
            'message' => $message,
            'raw' => $raw,
        ];
    }
}
