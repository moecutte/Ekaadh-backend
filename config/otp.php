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
    | When true, OTP send responses include debug_code (for staging without SMS).
    | Never enable in production with real users. Fixed OTP alone also exposes it.
    */
    'expose_debug_code' => (bool) env('OTP_EXPOSE_DEBUG_CODE', false),

    'ttl_seconds' => (int) env('OTP_TTL_SECONDS', 600),

    'length' => 6,

    'purposes' => ['register', 'checkout', 'find_tickets'],

    /*
    | Sent as a normal Telesom SMS until the account is OTP prepaid.
    | Placeholders: :code, :minutes
    */
    'sms_message' => env(
        'OTP_SMS_MESSAGE',
        'Your Ekaadh code is :code. Valid for :minutes minutes.'
    ),

];
