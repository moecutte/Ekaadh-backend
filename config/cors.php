<?php

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('CORS_ALLOWED_ORIGINS', '*'))
    ))),

    // Flutter web debug uses http://localhost:<random-port>.
    // Override with CORS_ALLOWED_ORIGIN_PATTERNS= to disable, or set your own regex list.
    'allowed_origins_patterns' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env(
            'CORS_ALLOWED_ORIGIN_PATTERNS',
            '#^http://localhost:\d+$#,#^http://127\.0\.0\.1:\d+$#'
        ))
    ))),

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
