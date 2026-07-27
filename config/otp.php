<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Fixed OTP (testing / no SMS provider)
    |--------------------------------------------------------------------------
    |
    | When set, every send uses this code and verify accepts it. Leave empty
    | in production so random codes are generated and delivered by SMS.
    |
    */
    'fixed_code' => env('OTP_FIXED_CODE', ''),

    'ttl_seconds' => (int) env('OTP_TTL_SECONDS', 600),

    'length' => 6,

    'purposes' => ['register', 'checkout', 'find_tickets'],

    /*
    | Placeholders: :code, :minutes
    */
    'sms_message' => env(
        'OTP_SMS_MESSAGE',
        'Your Ekaadh code is :code. Valid for :minutes minutes.'
    ),

];
