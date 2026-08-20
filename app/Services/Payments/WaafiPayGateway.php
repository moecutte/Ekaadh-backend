<?php

namespace App\Services\Payments;

use App\Support\PaymentMessage;
use App\Support\Phone;
use Illuminate\Support\Facades\Log;
use Throwable;

class WaafiPayGateway implements PaymentGatewayInterface
{
    public function __construct(private WaafiPayClient $client) {}

    public function name(): string
    {
        return 'waafipay';
    }

    public function initiate(float $amount, string $reference, array $options = []): array
    {
        if ((bool) ($options['force_fail'] ?? false)) {
            return [
                'status' => 'failed',
                'transaction_id' => 'WAAFI-FORCE-FAIL',
                'message' => __('ui.payment_failed_insufficient'),
                'raw' => [
                    'provider' => 'waafipay',
                    'reference' => $reference,
                    'amount' => $amount,
                    'result' => 'DECLINED',
                ],
            ];
        }

        if ((bool) config('waafipay.sandbox') && ! config('waafipay.has_sandbox_credentials')) {
            return [
                'status' => 'failed',
                'transaction_id' => 'WAAFI-SANDBOX-KEYS',
                'message' => __('ui.payment_failed_sandbox_credentials'),
                'raw' => [
                    'provider' => 'waafipay',
                    'reference' => $reference,
                    'amount' => $amount,
                    'result' => 'SANDBOX_KEYS_MISSING',
                ],
            ];
        }

        if (! $this->client->purchaseEnabled()) {
            return [
                'status' => 'failed',
                'transaction_id' => 'WAAFI-UNCONFIGURED',
                'message' => __('ui.payment_failed_unavailable'),
                'raw' => [
                    'provider' => 'waafipay',
                    'reference' => $reference,
                    'amount' => $amount,
                    'result' => 'NOT_CONFIGURED',
                ],
            ];
        }

        $accountNo = $this->accountNo($options['phone'] ?? null);
        if ($accountNo === '') {
            return [
                'status' => 'failed',
                'transaction_id' => 'WAAFI-INVALID-PHONE',
                'message' => __('ui.payment_failed_invalid_phone'),
                'raw' => [
                    'provider' => 'waafipay',
                    'reference' => $reference,
                    'result' => 'INVALID_PHONE',
                ],
            ];
        }

        $payerInfo = [
            'accountNo' => $accountNo,
        ];

        // Sandbox PIN is a local checkout gate only. WaafiPay API_PURCHASE
        // accepts payerInfo.accountNo — extra fields (e.g. accountHolderPin)
        // make sandbox return 1001 / "Request could not be processed".
        if ((bool) config('waafipay.sandbox')) {
            $pin = preg_replace('/\D+/', '', (string) ($options['pin'] ?? '')) ?? '';
            $expected = (string) config('waafipay.test_pin', '1212');
            if (strlen($pin) !== 4) {
                return [
                    'status' => 'failed',
                    'transaction_id' => 'WAAFI-PIN',
                    'message' => __('ui.wallet_pin_required'),
                    'raw' => [
                        'provider' => 'waafipay',
                        'reference' => $reference,
                        'result' => 'PIN_REQUIRED',
                    ],
                ];
            }
            if ($expected !== '' && ! hash_equals($expected, $pin)) {
                return [
                    'status' => 'failed',
                    'transaction_id' => 'WAAFI-PIN',
                    'message' => __('ui.payment_failed_wrong_pin'),
                    'raw' => [
                        'provider' => 'waafipay',
                        'reference' => $reference,
                        'result' => 'WRONG_PIN',
                    ],
                ];
            }
        }

        $amountStr = number_format($amount, 2, '.', '');
        $description = (string) ($options['description'] ?? 'Ekaadh order '.$reference);
        $description = mb_substr($description, 0, 255);

        try {
            $json = $this->client->call('API_PURCHASE', [
                'merchantUid' => (string) config('waafipay.merchant_uid'),
                'apiUserId' => (string) config('waafipay.api_user_id'),
                'apiKey' => (string) config('waafipay.api_key'),
                'paymentMethod' => 'MWALLET_ACCOUNT',
                'payerInfo' => $payerInfo,
                'transactionInfo' => [
                    'referenceId' => $reference,
                    'invoiceId' => $reference,
                    'amount' => $amountStr,
                    'currency' => (string) config('waafipay.currency', 'USD'),
                    'description' => $description,
                ],
            ]);
        } catch (Throwable $e) {
            Log::warning('WaafiPay purchase failed to connect', [
                'reference' => $reference,
                'error' => $e->getMessage(),
            ]);

            $ssl = str_contains(strtolower($e->getMessage()), 'ssl')
                || str_contains($e->getMessage(), 'certificate');

            if ($ssl) {
                return [
                    'status' => 'failed',
                    'transaction_id' => 'WAAFI-SSL-'.$reference,
                    'message' => __('ui.payment_failed_unavailable'),
                    'raw' => [
                        'provider' => 'waafipay',
                        'reference' => $reference,
                        'amount' => $amount,
                        'result' => 'SSL_ERROR',
                        'error' => $e->getMessage(),
                    ],
                ];
            }

            // Timeout: the wallet may already have been charged. Never mark failed.
            return [
                'status' => 'pending',
                'transaction_id' => 'WAAFI-TIMEOUT-'.$reference,
                'message' => __('ui.payment_confirming'),
                'raw' => [
                    'provider' => 'waafipay',
                    'reference' => $reference,
                    'amount' => $amount,
                    'result' => 'TIMEOUT',
                    'error' => $e->getMessage(),
                ],
            ];
        }

        return $this->interpret($json, $reference, $amount, $accountNo);
    }

    public function inquire(string $reference, ?string $transactionId = null): array
    {
        if (! $this->client->purchaseEnabled()) {
            return [
                'status' => 'unknown',
                'transaction_id' => $transactionId ?: $reference,
                'message' => __('ui.payment_confirming'),
                'raw' => [
                    'provider' => 'waafipay',
                    'reference' => $reference,
                    'result' => 'NOT_CONFIGURED',
                ],
            ];
        }

        try {
            $json = $this->client->call('API_GETTRANINFO', [
                'merchantUid' => (string) config('waafipay.merchant_uid'),
                'apiUserId' => (string) config('waafipay.api_user_id'),
                'apiKey' => (string) config('waafipay.api_key'),
                'referenceId' => $reference,
                'transactionId' => (string) $transactionId,
                'transactionInfo' => [
                    'referenceId' => $reference,
                    'invoiceId' => $reference,
                ],
            ]);
        } catch (Throwable $e) {
            Log::info('WaafiPay inquiry unavailable', [
                'reference' => $reference,
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => 'unknown',
                'transaction_id' => $transactionId ?: $reference,
                'message' => __('ui.payment_confirming'),
                'raw' => [
                    'provider' => 'waafipay',
                    'reference' => $reference,
                    'result' => 'INQUIRE_TIMEOUT',
                    'error' => $e->getMessage(),
                ],
            ];
        }

        $parsed = $this->interpret($json, $reference, 0, '');
        if ($parsed['status'] === 'failed') {
            $errorCode = (string) ($parsed['raw']['error_code'] ?? '');
            $state = strtoupper((string) ($parsed['raw']['result'] ?? ''));
            $notFound = $state === '' || in_array($errorCode, ['1001', '5304', '5001'], true);

            if ($notFound && ! in_array($state, ['DECLINED', 'FAILED', 'CANCELED', 'CANCELLED'], true)) {
                $parsed['status'] = 'unknown';
                $parsed['message'] = __('ui.payment_confirming');
                $parsed['raw']['result'] = $parsed['raw']['result'] ?: 'NOT_FOUND';
            }
        }

        return $parsed;
    }

    /**
     * @param  array<string, mixed>  $json
     * @return array{status: string, transaction_id: string, message: string, raw: array}
     */
    private function interpret(array $json, string $reference, float $amount, string $accountNo): array
    {
        $params = is_array($json['params'] ?? null) ? $json['params'] : [];
        $state = strtoupper((string) ($params['state'] ?? $params['status'] ?? $params['tranStatusDesc'] ?? ''));
        $transactionId = (string) ($params['transactionId'] ?? $params['transaction_id'] ?? '');
        $errorCode = (string) ($json['errorCode'] ?? '');
        $responseCode = (string) ($json['responseCode'] ?? '');
        $responseMsg = (string) ($json['responseMsg'] ?? '');

        $raw = [
            'provider' => 'waafipay',
            'reference' => $reference,
            'amount' => $amount,
            'account' => $accountNo !== '' ? $this->redact($accountNo) : null,
            'result' => $state !== '' ? $state : $responseMsg,
            'response_code' => $responseCode,
            'error_code' => $errorCode,
            'response' => $json,
        ];

        if (($errorCode === '0' || $errorCode === '') && in_array($state, ['APPROVED', 'SUCCESS', 'PAID', 'CAPTURED'], true)) {
            return [
                'status' => 'success',
                'transaction_id' => $transactionId !== '' ? $transactionId : $reference,
                'message' => 'Payment successful.',
                'raw' => $raw,
            ];
        }

        if (in_array($state, ['DECLINED', 'FAILED', 'CANCELED', 'CANCELLED'], true)
            || ($errorCode !== '' && $errorCode !== '0')) {
            return [
                'status' => 'failed',
                'transaction_id' => $transactionId !== '' ? $transactionId : 'WAAFI-FAILED',
                'message' => $this->userMessage($responseMsg, $state, $errorCode),
                'raw' => $raw,
            ];
        }

        if (in_array($state, ['PENDING', 'INITIATED', 'PROCESSING'], true)) {
            return [
                'status' => 'pending',
                'transaction_id' => $transactionId !== '' ? $transactionId : $reference,
                'message' => __('ui.payment_confirming'),
                'raw' => $raw,
            ];
        }

        Log::info('WaafiPay purchase not approved', [
            'reference' => $reference,
            'state' => $state,
            'response_code' => $responseCode,
        ]);

        return [
            'status' => 'failed',
            'transaction_id' => $transactionId !== '' ? $transactionId : 'WAAFI-FAILED',
            'message' => $this->userMessage($responseMsg, $state, $errorCode),
            'raw' => $raw,
        ];
    }

    /**
     * WaafiPay accountNo: country code + national number, digits only.
     * Never send + or a leading 0.
     */
    private function accountNo(?string $phone): string
    {
        return Phone::internationalDigits($phone);
    }

    private function userMessage(string $responseMsg, string $state, string $errorCode = ''): string
    {
        return PaymentMessage::fromProvider($responseMsg, $errorCode, $state);
    }

    public static function sandboxPin(?string $pin): ?string
    {
        if (! config('waafipay.sandbox')) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', (string) $pin) ?? '';
        $expected = (string) config('waafipay.test_pin', '1212');
        if (strlen($digits) !== 4) {
            return null;
        }
        if ($expected !== '' && ! hash_equals($expected, $digits)) {
            return null;
        }

        return $digits;
    }

    public static function sandboxPinError(?string $pin): string
    {
        $digits = preg_replace('/\D+/', '', (string) $pin) ?? '';
        if (strlen($digits) !== 4) {
            return __('ui.wallet_pin_required');
        }

        return __('ui.payment_failed_wrong_pin');
    }

    private function redact(string $phone): string
    {
        if (strlen($phone) < 4) {
            return '***';
        }

        return str_repeat('*', max(0, strlen($phone) - 4)).substr($phone, -4);
    }
}
