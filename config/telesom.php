<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Telesom Prepaid SMS Gateway
    |--------------------------------------------------------------------------
    |
    | REST API: https://sms.mytelesom.com
    | Auth: X-Auth-Key = Base64(HMAC-SHA256(SenderID + Timestamp + Username + Password))
    | HMAC key = TELESOM_SECRET_KEY (falls back to TELESOM_PASSWORD if empty).
    | Timestamp header is the calendar date in Africa/Mogadishu (YYYY-MM-DD).
    |
    */

    'base_url' => rtrim(env('TELESOM_BASE_URL', 'https://sms.mytelesom.com'), '/'),

    'sender_id' => env('TELESOM_SENDER_ID'),

    'username' => env('TELESOM_USERNAME'),

    'password' => env('TELESOM_PASSWORD'),

    'secret_key' => env('TELESOM_SECRET_KEY'),

    /*
    | Registered client reference. Must match the value Telesom assigned to the
    | account (CLIENT_REF_MISMATCH if it does not).
    */
    'client_ref' => env('TELESOM_CLIENT_REF'),

    'callback_url' => env('TELESOM_CALLBACK_URL'),

    'timeout' => (int) env('TELESOM_TIMEOUT', 20),

    /*
    | Windows/XAMPP: path to a CA bundle if cURL error 60. Falls back to
    | WAAFIPAY_CAFILE when unset.
    */
    'cafile' => env('TELESOM_CAFILE', env('WAAFIPAY_CAFILE')),

    'sms_path' => '/index.php/smsapi/v1/messages',

    'otp_path' => '/index.php/smsotpapi/v1/messages',

];
