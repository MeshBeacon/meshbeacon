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

    /*
    |--------------------------------------------------------------------------
    | Duck Crypto (mesh end-to-end encryption)
    |--------------------------------------------------------------------------
    |
    | This OpenDMS instance's fixed, static X25519 keypair (see
    | docs/crypto-design.tex in meshbeacon-firmware), raw 32-byte keys.
    | public_key is hex (64 chars) -- matches meshbeacon-firmware's
    | OPENDMS_STATIC_PUBLIC_KEY_HEX build flag exactly, so this value can
    | be pasted straight into both places with no re-encoding. private_key
    | stays base64 -- it's only ever consumed by DuckCryptoService's own
    | PHP code (never sent to a Duck or compiled into firmware), so there's
    | no interop reason to change it. Its public half is pinned into every
    | Duck's firmware at flash time; its private half must be backed up
    | normally (this is the one deliberate exception to the field-device
    | "no key backup" rule) -- losing it without a backup means every
    | already-fielded Duck must be re-flashed with a new public key (see
    | crypto-design.tex, "OpenDMS key loss / rotation requirement").
    |
    | Leave both empty to disable encryption entirely: App\Services\
    | DuckCryptoService::isConfigured() will return false, and callers
    | (e.g. SendSosAck) fall back to their existing unencrypted behavior.
    | This keeps the feature inert until meshbeacon-firmware's own send/
    | receive paths are wired to actually encrypt/decrypt (still pending;
    | DuckCrypto module itself is implemented but not yet called from
    | Duck::sendData()/handleReceivedPacket()).
    |
    */
    'duck_crypto' => [
        'private_key' => env('DUCK_CRYPTO_PRIVATE_KEY', ''),
        'public_key'  => env('DUCK_CRYPTO_PUBLIC_KEY', ''),
    ],

];
