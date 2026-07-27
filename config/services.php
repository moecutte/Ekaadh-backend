<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'payment' => [
        'gateway' => env('PAYMENT_GATEWAY', 'mock'),
    ],

    'zaad' => [
        'enabled' => env('ZAAD_ENABLED', false),
        'merchant_id' => env('ZAAD_MERCHANT_ID'),
        'api_key' => env('ZAAD_API_KEY'),
        'api_url' => env('ZAAD_API_URL'),
    ],

    'edahab' => [
        'enabled' => env('EDAHAB_ENABLED', false),
        'merchant_id' => env('EDAHAB_MERCHANT_ID'),
        'api_key' => env('EDAHAB_API_KEY'),
        'api_url' => env('EDAHAB_API_URL'),
    ],

    'ticket_qr' => [
        'secret' => env('TICKET_QR_SECRET'),
    ],

];
