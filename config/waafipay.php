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

$liveStoreId = env('WAAFIPAY_LIVE_HPP_STORE_ID', env('WAAFIPAY_HPP_STORE_ID'));
$liveHppKey = env('WAAFIPAY_LIVE_HPP_KEY', env('WAAFIPAY_HPP_KEY'));
$sandboxStoreId = env('WAAFIPAY_SANDBOX_HPP_STORE_ID');
$sandboxHppKey = env('WAAFIPAY_SANDBOX_HPP_KEY');

$storeId = $sandbox ? $sandboxStoreId : $liveStoreId;
$hppKey = $sandbox ? $sandboxHppKey : $liveHppKey;
$hppEnabled = filled($storeId) && filled($hppKey);

return [

    /*
    |--------------------------------------------------------------------------
    | WaafiPay Purchase API + Hosted Payment Page (cards)
    |--------------------------------------------------------------------------
    |
    | Docs: https://docs.waafipay.com/purchase-api
    | HPP:  https://docs.waafipay.com/hpp-api
    |
    | Mobile money uses API_PURCHASE (MWALLET_ACCOUNT).
    | Debit/credit cards (Visa, Mastercard) use HPP_PURCHASE (CREDIT_CARD).
    | Fill WAAFIPAY_*_HPP_STORE_ID and WAAFIPAY_*_HPP_KEY from the WaafiPay dashboard.
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

    'store_id' => $storeId,

    'hpp_key' => $hppKey,

    'hpp_enabled' => $hppEnabled,

    /*
    | Set WAAFIPAY_CARD_CHECKOUT=true to show Visa/Mastercard at checkout.
    | Also requires HPP store ID + key (hpp_enabled).
    */
    'card_checkout_enabled' => filter_var(env('WAAFIPAY_CARD_CHECKOUT', false), FILTER_VALIDATE_BOOL)
        && $hppEnabled,

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

    /*
    | Sandbox cards from https://docs.waafipay.com/quickstart
    */
    'test_cards' => [
        ['network' => 'Visa', 'number' => '4111111111111111', 'expiry' => '12/26', 'cvv' => '123'],
        ['network' => 'Mastercard', 'number' => '5555555555555599', 'expiry' => '12/34', 'cvv' => '123'],
    ],

];
