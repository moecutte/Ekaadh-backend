<?php

namespace App\Support;

use App\Services\Payments\WaafiPayGateway;

class CheckoutPayments
{
    /**
     * @return list<string>
     */
    public static function methods(): array
    {
        $methods = ['waafipay'];
        if ((bool) config('waafipay.card_checkout_enabled')) {
            $methods[] = 'waafipay_card';
        }
        if ((bool) config('edahab.ready')) {
            $methods[] = 'edahab';
        }

        return $methods;
    }

    public static function rule(): string
    {
        return implode(',', self::methods());
    }

    public static function walletPinError(?string $method, ?string $pin): ?string
    {
        if ($method !== 'waafipay' || ! config('waafipay.sandbox')) {
            return null;
        }

        if (WaafiPayGateway::sandboxPin($pin) !== null) {
            return null;
        }

        return WaafiPayGateway::sandboxPinError($pin);
    }
}
