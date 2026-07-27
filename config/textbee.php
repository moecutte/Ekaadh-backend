<?php

return [

    'base_url' => rtrim(env('TEXTBEE_BASE_URL', 'https://api.textbee.dev/api/v1'), '/'),

    'api_key' => env('TEXTBEE_API_KEY'),

    'device_id' => env('TEXTBEE_DEVICE_ID'),

    'timeout' => (int) env('TEXTBEE_TIMEOUT', 20),

];
