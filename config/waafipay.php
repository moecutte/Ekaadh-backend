<?php

$mode = strtolower((string) env('WAAFIPAY_MODE', ''));
if (! in_array($mode, ['sandbox', 'live'], true)) {
    $mode = str_contains((string) env('WAAFIPAY_BASE_URL', ''), 'sandbox') ? 'sandbox' : 'live';
}

$sandbox = $mode === 'sandbox';

$liveMerchant = env('WAAFIPAY_LIVE_MERCHANT_UID', env('WAAFIPAY_MERCHANT_UID'));
$liveUserId = env('WAAFIPAY_LIVE_API_USER_ID', env('WAAFIPAY_API_USER_ID'));
$liveApiKey = env('WAAFIPAY_LIVE_API_KEY', env('WAAFIPAY_API_KEY'));

$sandboxMerchant = env('WAAFIPAY_SANDBOX_MERCHANT_UID');
$sandboxUserId = env('WAAFIPAY_SANDBOX_API_USER_ID');
$sandboxApiKey = env('WAAFIPAY_SANDBOX_API_KEY');

$hasSandboxCredentials = filled($sandboxMerchant) && filled($sandboxUserId) && filled($sandboxApiKey);

return [

    /*
    |--------------------------------------------------------------------------
    | WaafiPay Purchase API
    |--------------------------------------------------------------------------
    |
    | Docs: https://docs.waafipay.com/purchase-api
    |
    | Switch environments with WAAFIPAY_MODE=sandbox|live in .env.
    | Sandbox rejects live merchant keys (error 1001 / "Authentication failed").
    | Fill WAAFIPAY_SANDBOX_* with keys from the WaafiPay sandbox dashboard.
    |
    */

    'mode' => $mode,

    'sandbox' => $sandbox,

    'has_sandbox_credentials' => $hasSandboxCredentials,

    'base_url' => rtrim((string) (
        $sandbox
            ? env('WAAFIPAY_SANDBOX_URL', env('WAAFIPAY_BASE_URL', 'https://sandbox.waafipay.com/asm'))
            : env('WAAFIPAY_LIVE_URL', env('WAAFIPAY_BASE_URL', 'https://api.waafipay.net/asm'))
    ), '/'),

    'merchant_uid' => $sandbox ? $sandboxMerchant : $liveMerchant,

    'api_user_id' => $sandbox ? $sandboxUserId : $liveUserId,

    'api_key' => $sandbox ? $sandboxApiKey : $liveApiKey,

    'currency' => env('WAAFIPAY_CURRENCY', 'USD'),

    'timeout' => (int) env('WAAFIPAY_TIMEOUT', 45),

    'channel' => env('WAAFIPAY_CHANNEL', 'WEB'),

    'cafile' => env('WAAFIPAY_CAFILE'),

    'test_pin' => (string) env('WAAFIPAY_SANDBOX_PIN', '1212'),

    /*
    | Sandbox wallets from https://docs.waafipay.com/quickstart (PIN 1212).
    | Checkout +252 field: enter the local part only (e.g. 611111111).
    */
    'test_wallets' => [
        ['brand' => 'EVCPlus', 'provider' => 'Hormuud', 'account' => '252611111111', 'local' => '611111111'],
        ['brand' => 'ZAAD', 'provider' => 'Telesom', 'account' => '252631111111', 'local' => '631111111'],
        ['brand' => 'SAHAL', 'provider' => 'Golis', 'account' => '252901111111', 'local' => '901111111'],
    ],

];
