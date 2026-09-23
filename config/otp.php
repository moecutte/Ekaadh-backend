<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Fixed OTP (testing / no SMS provider)
    |--------------------------------------------------------------------------
    |
    | When set, every send uses this code and verify accepts it. Leave empty
    | in production so random codes are generated and delivered by Telesom SMS.
    |
    */
    'fixed_code' => env('OTP_FIXED_CODE', ''),

    /*
    | When true, production boot is rejected. Codes are never returned in API JSON.
    */
    'expose_debug_code' => (bool) env('OTP_EXPOSE_DEBUG_CODE', false),

    'ttl_seconds' => (int) env('OTP_TTL_SECONDS', 600),

    'length' => 6,

    'purposes' => ['register', 'checkout', 'find_tickets'],

    /*
    | Unused by the Telesom OTP API (that endpoint delivers the numeric code
    | only). Kept so existing env files do not break.
    */
    'sms_message' => env(
        'OTP_SMS_MESSAGE',
        'Your Ekaadh code is :code. Valid for :minutes minutes.'
    ),

];
