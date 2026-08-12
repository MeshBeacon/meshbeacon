<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Throwable;

class QueueStatus
{
    public const CACHE_KEY = 'meshbeacon:observability:queue';

    private const PERSIST_INTERVAL = 15;

    /**
     * @param  array<string, mixed>  $context
     */
    public function markProcessing(array $context): void
    {
        $this->throttledUpdate([
            'last_processing_at' => now()->toIso8601String(),
            'last_processing' => $context,
        ]);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function markProcessed(array $context): void
    {
        $this->throttledUpdate([
            'last_processed_at' => now()->toIso8601String(),
            'last_processed' => $context,
        ]);
    }

    /**
     * Store the latest failure immediately so the operations page can surface
     * an actionable alert even when the failed-jobs table is not monitored.
     *
     * @param  array<string, mixed>  $context
     */
    public function markFailed(array $context): void
    {
        $state = $this->snapshot();
        $state['last_failure_at'] = now()->toIso8601String();
        $state['last_failure'] = [
            'failed_at' => $state['last_failure_at'],
            'connection' => $context['connection'] ?? 'unknown',
            'queue' => $context['queue'] ?? 'unknown',
            'job' => $context['job'] ?? 'unknown',
            'job_id' => $context['job_id'] ?? null,
            'exception_class' => $context['exception_class'] ?? 'Throwable',
            'exception_message' => Str::limit((string) ($context['exception_message'] ?? ''), 500),
        ];

        $this->store($state);
    }

    /**
     * @return array<string, mixed>
     */
    public function snapshot(): array
    {
        $defaults = [
            'last_processing_at' => null,
            'last_processed_at' => null,
            'last_failure_at' => null,
            'last_processing' => null,
            'last_processed' => null,
            'last_failure' => null,
        ];

        try {
            $state = Cache::get(self::CACHE_KEY, []);

            return array_merge($defaults, is_array($state) ? $state : []);
        } catch (Throwable) {
            return $defaults;
        }
    }

    /**
     * @param  array<string, mixed>  $changes
     */
    private function throttledUpdate(array $changes): void
    {
        try {
            $state = $this->snapshot();
            $lastActivity = $state['last_processing_at'] ?? $state['last_processed_at'];

            if ($lastActivity && now()->diffInSeconds($lastActivity, true) < self::PERSIST_INTERVAL) {
                return;
            }

            $this->store(array_merge($state, $changes));
        } catch (Throwable) {
            // Observability must never change queue behavior.
        }
    }

    /**
     * @param  array<string, mixed>  $state
     */
    private function store(array $state): void
    {
        try {
            Cache::forever(self::CACHE_KEY, $state);
        } catch (Throwable) {
            // The queue event listener is deliberately best effort.
        }
    }
}
