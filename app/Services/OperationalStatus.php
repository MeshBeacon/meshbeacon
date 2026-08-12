<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class OperationalStatus
{
    public function __construct(
        private readonly MqttStatus $mqttStatus,
        private readonly QueueStatus $queueStatus,
        private readonly WorkerStatus $workerStatus,
    ) {}

    /**
     * Return the complete status document. Public callers receive no exception
     * messages or other details that could reveal infrastructure information.
     *
     * @return array<string, mixed>
     */
    public function snapshot(bool $private = false): array
    {
        $database = $this->databaseCheck();
        $migrations = $database['ready'] ? $this->migrationCheck() : [
            'status' => 'blocked',
            'ready' => false,
            'pending' => null,
            'pending_migrations' => [],
        ];
        $queue = $database['ready'] ? $this->queueCheck() : [
            'status' => 'blocked',
            'ready' => false,
            'driver' => config('queue.default'),
            'pending' => null,
            'failed' => null,
            'oldest_pending_at' => null,
            'last_failure_at' => null,
            'last_failure' => null,
        ];
        $mqtt = $this->mqttCheck($private);
        $workers = $this->workerChecks($private);

        $required = [
            $database['ready'],
            $migrations['ready'],
            $queue['ready'],
            $mqtt['ready'],
        ];

        if (config('observability.workers_required', true)) {
            foreach ($workers as $worker) {
                $required[] = $worker['ready'];
            }
        }

        $ready = ! in_array(false, $required, true);
        $hasWarning = ($queue['status'] ?? null) === 'warning'
            || collect($workers)->contains(fn (array $worker): bool => $worker['status'] === 'warning');

        return [
            'status' => $ready && ! $hasWarning ? 'ok' : 'degraded',
            'ready' => $ready,
            'generated_at' => now()->toIso8601String(),
            'application' => [
                'name' => config('app.name'),
                'environment' => app()->environment(),
                'version' => env('APP_VERSION'),
            ],
            'checks' => [
                'database' => $database,
                'migrations' => $migrations,
                'mqtt' => $mqtt,
                'queue' => $queue,
                'workers' => $workers,
            ],
        ];
    }

    public function applicationReady(): bool
    {
        $database = $this->databaseCheck();

        return $database['ready'] && $this->migrationCheck()['ready'];
    }

    public function migrationsReady(): bool
    {
        return $this->migrationCheck()['ready'];
    }

    public function workerReady(string $worker): bool
    {
        return $this->workerStatus->isHealthy($worker);
    }

    /**
     * Generate a Prometheus text exposition document from a status snapshot.
     * The endpoint intentionally uses aggregate values and no message payloads.
     *
     * @param  array<string, mixed>  $snapshot
     */
    public function prometheus(array $snapshot): string
    {
        $checks = $snapshot['checks'];
        $queue = $checks['queue'];
        $mqtt = $checks['mqtt'];
        $lines = [
            '# HELP meshbeacon_health_ready Whether all required readiness checks pass.',
            '# TYPE meshbeacon_health_ready gauge',
            'meshbeacon_health_ready '.($snapshot['ready'] ? 1 : 0),
            '# HELP meshbeacon_database_up Whether the application database is reachable.',
            '# TYPE meshbeacon_database_up gauge',
            'meshbeacon_database_up '.(($checks['database']['ready'] ?? false) ? 1 : 0),
            '# HELP meshbeacon_migrations_pending Number of unapplied database migrations.',
            '# TYPE meshbeacon_migrations_pending gauge',
            'meshbeacon_migrations_pending '.(int) ($checks['migrations']['pending'] ?? 0),
            '# HELP meshbeacon_queue_pending_jobs Number of pending database queue jobs.',
            '# TYPE meshbeacon_queue_pending_jobs gauge',
            'meshbeacon_queue_pending_jobs '.(int) ($queue['pending'] ?? 0),
            '# HELP meshbeacon_queue_failed_jobs Number of failed queue jobs.',
            '# TYPE meshbeacon_queue_failed_jobs gauge',
            'meshbeacon_queue_failed_jobs '.(int) ($queue['failed'] ?? 0),
            '# HELP meshbeacon_queue_failure_alert Whether failed jobs currently require attention.',
            '# TYPE meshbeacon_queue_failure_alert gauge',
            'meshbeacon_queue_failure_alert '.(($queue['status'] ?? null) === 'warning' ? 1 : 0),
            '# HELP meshbeacon_mqtt_connected Whether the MQTT subscriber has a live broker connection.',
            '# TYPE meshbeacon_mqtt_connected gauge',
            'meshbeacon_mqtt_connected '.(($mqtt['connection_status'] ?? null) === 'connected' ? 1 : 0),
        ];

        foreach ($checks['workers'] as $worker => $state) {
            $lines[] = 'meshbeacon_worker_up{worker="'.$worker.'"} '.($state['ready'] ? 1 : 0);
        }

        return implode("\n", $lines)."\n";
    }

    /**
     * @return array<string, mixed>
     */
    private function databaseCheck(): array
    {
        $startedAt = microtime(true);

        try {
            DB::connection()->getPdo();
            DB::select('select 1');

            return [
                'status' => 'ok',
                'ready' => true,
                'latency_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            ];
        } catch (Throwable) {
            return [
                'status' => 'failed',
                'ready' => false,
                'latency_ms' => null,
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function migrationCheck(): array
    {
        try {
            if (! Schema::hasTable('migrations')) {
                return [
                    'status' => 'failed',
                    'ready' => false,
                    'pending' => null,
                    'pending_migrations' => [],
                ];
            }

            $files = collect(glob(database_path('migrations').'/*.php') ?: [])
                ->map(fn (string $path): string => pathinfo($path, PATHINFO_FILENAME));
            $ran = DB::table('migrations')->pluck('migration');
            $pending = $files->diff($ran)->values()->all();

            return [
                'status' => $pending === [] ? 'ok' : 'failed',
                'ready' => $pending === [],
                'pending' => count($pending),
                'pending_migrations' => $pending,
            ];
        } catch (Throwable) {
            return [
                'status' => 'failed',
                'ready' => false,
                'pending' => null,
                'pending_migrations' => [],
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function queueCheck(): array
    {
        $driver = (string) config('queue.default');
        $state = $this->queueStatus->snapshot();
        $pending = null;
        $failed = 0;
        $oldestPendingAt = null;
        $lastFailureAt = $state['last_failure_at'];

        try {
            if ($driver === 'database') {
                $table = (string) config('queue.connections.database.table', 'jobs');

                if (! Schema::hasTable($table)) {
                    return [
                        'status' => 'failed',
                        'ready' => false,
                        'driver' => $driver,
                        'pending' => null,
                        'failed' => null,
                        'oldest_pending_at' => null,
                        'last_failure_at' => $lastFailureAt,
                        'last_failure' => $state['last_failure'],
                    ];
                }

                $pending = DB::table($table)->count();
                $oldest = DB::table($table)->min('available_at');
                $oldestPendingAt = $oldest ? CarbonImmutable::createFromTimestampUTC((int) $oldest)->toIso8601String() : null;
            }

            $failedTable = (string) config('queue.failed.table', 'failed_jobs');
            if (Schema::hasTable($failedTable)) {
                $failed = DB::table($failedTable)->count();
                $databaseLastFailure = DB::table($failedTable)->max('failed_at');
                $lastFailureAt = $databaseLastFailure ?: $lastFailureAt;
            }

            return [
                'status' => $failed > 0 ? 'warning' : 'ok',
                'ready' => true,
                'driver' => $driver,
                'pending' => $pending,
                'failed' => $failed,
                'oldest_pending_at' => $oldestPendingAt,
                'last_failure_at' => $lastFailureAt,
                'last_failure' => $state['last_failure'],
            ];
        } catch (Throwable) {
            return [
                'status' => 'failed',
                'ready' => false,
                'driver' => $driver,
                'pending' => $pending,
                'failed' => $failed,
                'oldest_pending_at' => $oldestPendingAt,
                'last_failure_at' => $lastFailureAt,
                'last_failure' => $state['last_failure'],
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function mqttCheck(bool $private): array
    {
        $state = $this->mqttStatus->snapshot();
        $required = (bool) config('observability.mqtt_required', true);
        $connectionHealthy = $this->mqttStatus->isHealthy();

        return [
            'status' => ! $required ? 'disabled' : ($connectionHealthy ? 'ok' : 'failed'),
            'ready' => ! $required || $connectionHealthy,
            'required' => $required,
            'connection_status' => $this->mqttStatus->connectionState(),
            'connected_at' => $state['connected_at'],
            'last_message_at' => $state['last_message_at'],
            'last_heartbeat_at' => $state['last_heartbeat_at'],
            'last_error' => $private ? $state['last_error'] : null,
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function workerChecks(bool $private): array
    {
        $required = (bool) config('observability.workers_required', true);
        $workers = [];

        foreach (WorkerStatus::workers() as $worker) {
            $state = $this->workerStatus->snapshot($worker);
            $healthy = $this->workerStatus->isHealthy($worker);
            $workers[$worker] = [
                'status' => $healthy ? 'ok' : ($required ? 'failed' : 'warning'),
                'ready' => $healthy || ! $required,
                'required' => $required,
                'last_heartbeat_at' => $state['last_heartbeat_at'],
            ];
        }

        return $workers;
    }
}
