<?php

$enabled = filter_var(env('EDAHAB_ENABLED', false), FILTER_VALIDATE_BOOL);
$apiKey = env('EDAHAB_API_KEY');
$apiSecret = env('EDAHAB_API_SECRET');
$agentCode = env('EDAHAB_AGENT_CODE', env('EDAHAB_MERCHANT_ID'));
$currency = strtoupper((string) env('EDAHAB_CURRENCY', 'USD'));
if (! in_array($currency, ['USD', 'SLSH'], true)) {
    $currency = 'USD';
}

return [

    /*
    |--------------------------------------------------------------------------
    | eDahab mobile money (Somtel)
    |--------------------------------------------------------------------------
    |
    | Docs: https://docs.edahab.net/
    | Purchase push/pop-up: POST /Issueinvoice
    | Confirm a pending invoice: POST /CheckInvoiceStatus
    |
    | Every request is JSON. The ?hash= query value is SHA-256 hex of the
    | exact request body with the API secret appended.
    |
    */

    'enabled' => $enabled,
    'ready' => $enabled && filled($apiKey) && filled($apiSecret) && filled($agentCode),
    'api_key' => $apiKey,
    'api_secret' => $apiSecret,
    'agent_code' => $agentCode,
    'currency' => $currency,
    'base_url' => env('EDAHAB_API_URL') ?: 'https://edahab.net/api/api/',
    'timeout' => (int) env('EDAHAB_TIMEOUT', 120),
    'cafile' => env('EDAHAB_CAFILE', env('WAAFIPAY_CAFILE')),

];
