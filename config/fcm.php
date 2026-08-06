<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Firebase Cloud Messaging (HTTP v1)
    |--------------------------------------------------------------------------
    |
    | Set FCM_PROJECT_ID and point FCM_CREDENTIALS to a Firebase service
    | account JSON file. When unset, push sends are logged and skipped.
    |
    */

    'project_id' => env('FCM_PROJECT_ID'),

    'credentials' => env('FCM_CREDENTIALS', storage_path('app/firebase-credentials.json')),

    'enabled' => (bool) env('FCM_ENABLED', false),

];
