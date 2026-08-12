<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Throwable;

class WorkerStatus
{
    public const WORKERS = ['mqtt', 'queue', 'scheduler'];

    private const CACHE_PREFIX = 'meshbeacon:observability:worker:';

    /**
     * @return list<string>
     */
    public static function workers(): array
    {
        return self::WORKERS;
    }

    public function heartbeat(string $worker): void
    {
        if (! in_array($worker, self::WORKERS, true)) {
            return;
        }

        try {
            Cache::forever(self::key($worker), [
                'worker' => $worker,
                'status' => 'running',
                'last_heartbeat_at' => now()->toIso8601String(),
            ]);
        } catch (Throwable) {
            // A cache outage must not terminate the worker being observed.
        }
    }

    /**
     * @return array{worker:string,status:string,last_heartbeat_at:?string}
     */
    public function snapshot(string $worker): array
    {
        $defaults = [
            'worker' => $worker,
            'status' => 'unknown',
            'last_heartbeat_at' => null,
        ];

        if (! in_array($worker, self::WORKERS, true)) {
            return $defaults;
        }

        try {
            $state = Cache::get(self::key($worker), []);

            return array_merge($defaults, is_array($state) ? $state : []);
        } catch (Throwable) {
            return $defaults;
        }
    }

    public function isHealthy(string $worker): bool
    {
        $state = $this->snapshot($worker);

        if ($state['status'] !== 'running' || ! $state['last_heartbeat_at']) {
            return false;
        }

        try {
            return now()->diffInSeconds($state['last_heartbeat_at'], true)
                <= (int) config('observability.worker_heartbeat_ttl', 45);
        } catch (Throwable) {
            return false;
        }
    }

    private static function key(string $worker): string
    {
        return self::CACHE_PREFIX.$worker;
    }
}
