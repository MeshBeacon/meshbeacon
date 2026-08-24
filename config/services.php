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

    /*
    |--------------------------------------------------------------------------
    | TAK CoT Bridge (see docs/TAK_BRIDGE.md)
    |--------------------------------------------------------------------------
    |
    | The TAK bridge itself is a separate, standalone service (its own
    | docker-compose.yml) that forwards CoT XML to TAK/WinTAK and publishes
    | a summary of each event to the `hub/tak/log` MQTT topic, which this
    | app records via TakLog. This flag only controls whether the "TAK Logs"
    | nav link is shown — it does not start or configure the bridge service.
    | Leave TAK_BRIDGE_ENABLED false/unset if the bridge isn't deployed.
    |
    */
    'tak' => [
        'enabled' => (bool) env('TAK_BRIDGE_ENABLED', false),
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

        // This deployment's pre-shared mesh group symmetric key (see
        // meshbeacon-firmware's src/security/MeshGroupConfig.h), hex (64
        // chars) -- matches the MESH_GROUP_KEY_HEX build flag exactly, so
        // this value can be pasted straight into both places with no
        // re-encoding. Unlike the OpenDMS keypair above, this key IS
        // secret: anyone holding it can both encrypt and decrypt group
        // broadcast traffic, so treat it the same as any other pre-shared
        // symmetric secret. Used by DuckCryptoService::encryptGroupBroadcast()
        // to authenticate StatusController::broadcast() ("Emergency
        // broadcast"), since encrypted_cmd can't address a broadcast (it's
        // a point-to-point channel, a different shared secret per Duck).
        // Leave empty to disable: broadcasts are then sent in the clear,
        // same as before this key existed.
        'mesh_group_key' => env('DUCK_MESH_GROUP_KEY', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | OpenTAKServer Encrypted Bridge (see docs/OPENTAK_BRIDGE.md)
    |--------------------------------------------------------------------------
    |
    | This is a distinct static X25519 keypair from duck_crypto above -- it
    | authenticates the MeshBeacon <-> OpenTAKServer plugin link specifically,
    | so a compromise of one channel's key does not affect the other. Both
    | keys are raw 32 bytes: public_key hex (64 chars, matches the OTS
    | plugin's OTS_MESHBEACON_PUBLIC_KEY env var so it can be pasted verbatim),
    | private_key base64 (only ever consumed by this app's own PHP code).
    |
    | peer_public_key is the OpenTAKServer plugin's own static public key
    | (hex, 64 chars), obtained from the plugin's `get_info()`/setup output
    | on the OTS side. Both this app and the OTS plugin must have each
    | other's public key configured before the link will decrypt anything --
    | see isConfigured() in OpenTakCryptoService.
    |
    | event_topic/command_topic are the MQTT topics this bridge uses on this
    | app's own Mosquitto broker: MeshBeacon publishes encrypted telemetry on
    | event_topic, and the OTS plugin (an MQTT client connecting to this
    | broker, the same way the standalone TAK bridge does) publishes
    | encrypted mesh commands on command_topic.
    |
    | Leave enabled=false (or either keypair/peer key empty) to disable the
    | bridge entirely: ProcessMqttMessage will not dispatch
    | PublishOpenTakEvent, and incoming command_topic messages are ignored.
    |
    */
    'opentak' => [
        'enabled' => (bool) env('OPENTAK_BRIDGE_ENABLED', false),
        'private_key' => env('OPENTAK_BRIDGE_PRIVATE_KEY', ''),
        'public_key' => env('OPENTAK_BRIDGE_PUBLIC_KEY', ''),
        'peer_public_key' => env('OPENTAK_SERVER_PUBLIC_KEY', ''),
        'event_topic' => env('OPENTAK_EVENT_TOPIC', 'hub/opentak/event'),
        'command_topic' => env('OPENTAK_COMMAND_TOPIC', 'hub/opentak/command'),
    ],

];
