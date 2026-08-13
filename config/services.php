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

    /*
    |--------------------------------------------------------------------------
    | Central DMS (Hybrid Deployment)
    |--------------------------------------------------------------------------
    |
    | Used only in hybrid store-and-forward mode. Leave CENTRAL_DMS_URL empty
    | (or unset) for production deployments (this IS the central server) and
    | for fully offline standalone field deployments.
    |
    | When set, ProcessMqttMessage will dispatch SyncRecordToCloud jobs that
    | POST each incoming record to the central server's /api/ingest endpoint,
    | retrying with exponential backoff until delivery is confirmed.
    |
    */
    'central_dms' => [
        'url'   => env('CENTRAL_DMS_URL', ''),
        'token' => env('CENTRAL_DMS_TOKEN', ''),

        // When true, this instance's incident-dispatch write actions
        // (acknowledge, assign, notes, resolve, bulk-acknowledge) are
        // rejected with 403 and hidden in the UI. Intended for a hybrid
        // central aggregator, where dispatch happens only at the field
        // site and central is monitoring-only (see docs/HYBRID_DEPLOYMENT.md).
        'dashboard_readonly' => (bool) env('DASHBOARD_READONLY', false),
    ],

    'map' => [
        'mbtiles_path' => env('MAP_MBTILES_PATH', storage_path('app/map.mbtiles')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Telegram Bot (SOS alerting)
    |--------------------------------------------------------------------------
    |
    | Free, low-friction alternative to WhatsApp/SMS for pushing SOS alerts
    | to responders. Leave TELEGRAM_BOT_TOKEN empty to disable alerting
    | entirely (SendTelegramAlert becomes a no-op).
    |
    */
    'telegram' => [
        'bot_token'      => env('TELEGRAM_BOT_TOKEN', ''),
        'bot_username'   => env('TELEGRAM_BOT_USERNAME', ''),
        'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET', ''),
    ],

];
