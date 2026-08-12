<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Readiness policy
    |--------------------------------------------------------------------------
    |
    | MQTT and worker processes are required by the default Docker deployment.
    | A deployment that intentionally runs without one of them can disable
    | that requirement while keeping the status page and metrics available.
    |
    */

    'mqtt_required' => env('OBSERVABILITY_MQTT_REQUIRED', true),

    'workers_required' => env('OBSERVABILITY_WORKERS_REQUIRED', true),

    'mqtt_heartbeat_ttl' => (int) env('OBSERVABILITY_MQTT_HEARTBEAT_TTL', 45),

    'worker_heartbeat_ttl' => (int) env('OBSERVABILITY_WORKER_HEARTBEAT_TTL', 45),

    'heartbeat_interval' => (int) env('OBSERVABILITY_HEARTBEAT_INTERVAL', 15),

    'queue_failure_window' => (int) env('OBSERVABILITY_QUEUE_FAILURE_WINDOW', 900),

    /*
    |--------------------------------------------------------------------------
    | Metrics access
    |--------------------------------------------------------------------------
    |
    | Leave empty for a private network or local scrape. Set a bearer token
    | before exposing /metrics outside the trusted network.
    |
    */

    'metrics_token' => env('OBSERVABILITY_METRICS_TOKEN', ''),

];
