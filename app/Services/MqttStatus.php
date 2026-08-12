<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Throwable;

class MqttStatus
{
    public const CACHE_KEY = 'meshbeacon:observability:mqtt';

    private const PERSIST_INTERVAL = 15;

    /**
     * Mark the subscriber as starting before it attempts a broker connection.
     */
    public function markStarting(): void
    {
        $this->update([
            'status' => 'starting',
            'last_error' => null,
        ], force: true);
    }

    /**
     * Mark a successful broker connection and subscription.
     */
    public function markConnected(): void
    {
        $now = now()->toIso8601String();

        $this->update([
            'status' => 'connected',
            'connected_at' => $now,
            'last_heartbeat_at' => $now,
            'last_error' => null,
        ], force: true);
    }

    /**
     * Record that the subscriber is still alive. The cache write is throttled
     * because the database cache driver would otherwise write once per packet.
     */
    public function markWorkerHeartbeat(): void
    {
        $this->update([
            'last_heartbeat_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Whether an idle-keepalive heartbeat is actually needed right now. Real
     * message traffic already refreshes last_heartbeat_at via markMessage(),
     * so this only returns true once that value is getting stale - keeping the
     * synthetic heartbeat from competing with message persistence for the
     * shared persist-throttle window during periods of active traffic.
     */
    public function needsWorkerHeartbeat(): bool
    {
        $state = $this->snapshot();
        $ttl = (int) config('observability.mqtt_heartbeat_ttl', 45);
        $refreshAfter = max(5, intdiv($ttl, 2));

        return ! $this->isRecent($state['last_heartbeat_at'], $refreshAfter);
    }

    /**
     * Record an incoming packet and periodically persist its timestamp.
     */
    public function markMessage(): void
    {
        $now = now()->toIso8601String();

        $this->update([
            'last_message_at' => $now,
            'last_heartbeat_at' => $now,
        ]);
    }

    public function markDisconnected(?string $reason = null): void
    {
        $this->update([
            'status' => 'disconnected',
            'last_error' => $reason,
        ], force: true);
    }

    public function markError(Throwable|string $error): void
    {
        $message = $error instanceof Throwable
            ? get_class($error).': '.$error->getMessage()
            : $error;

        $this->update([
            'status' => 'error',
            'last_error' => Str::limit($message, 300),
        ], force: true);
    }

    /**
     * Return only non-sensitive connection state suitable for an operations
     * page. Cache failures are represented as unknown instead of breaking the
     * health endpoint that is reporting the failure.
     *
     * @return array<string, mixed>
     */
    public function snapshot(): array
    {
        $defaults = [
            'status' => 'unknown',
            'connected_at' => null,
            'last_message_at' => null,
            'last_heartbeat_at' => null,
            'last_error' => null,
            'last_message_persisted_at' => null,
            'last_heartbeat_persisted_at' => null,
        ];

        try {
            $state = Cache::get(self::CACHE_KEY, []);

            return array_merge($defaults, is_array($state) ? $state : []);
        } catch (Throwable) {
            return $defaults;
        }
    }

    public function isHealthy(): bool
    {
        if (! config('observability.mqtt_required', true)) {
            return true;
        }

        $state = $this->snapshot();

        return $state['status'] === 'connected'
            && $this->isRecent($state['last_heartbeat_at'], (int) config('observability.mqtt_heartbeat_ttl', 45));
    }

    /**
     * The connection state to display. A cached "connected" status is only
     * trustworthy while the heartbeat stays fresh; once it goes stale (worker
     * crashed, was killed, or the broker link died silently) this reports
     * "disconnected" instead of echoing the last known status forever.
     */
    public function connectionState(): string
    {
        $state = $this->snapshot();

        if ($state['status'] === 'connected'
            && ! $this->isRecent($state['last_heartbeat_at'], (int) config('observability.mqtt_heartbeat_ttl', 45))) {
            return 'disconnected';
        }

        return $state['status'];
    }

    /**
     * @param  array<string, mixed>  $changes
     */
    private function update(array $changes, bool $force = false): void
    {
        try {
            $state = $this->snapshot();
            $previousStatus = $state['status'];
            $state = array_merge($state, $changes);
            $now = now();

            // Message and heartbeat writes are throttled independently so a
            // synthetic keepalive heartbeat can never consume the persist
            // window that real message activity needs in order to record
            // itself (and vice versa).
            $throttleKey = array_key_exists('last_message_at', $changes)
                ? 'last_message_persisted_at'
                : 'last_heartbeat_persisted_at';
            $lastPersistedAt = $state[$throttleKey];

            $shouldPersist = $force
                || $previousStatus !== $state['status']
                || ! $lastPersistedAt
                || ! $this->isRecent($lastPersistedAt, self::PERSIST_INTERVAL);

            if (! $shouldPersist) {
                return;
            }

            $state[$throttleKey] = $now->toIso8601String();
            Cache::forever(self::CACHE_KEY, $state);
        } catch (Throwable) {
            // Observability must never stop message ingestion.
        }
    }

    private function isRecent(?string $timestamp, int $ttl): bool
    {
        if (! $timestamp) {
            return false;
        }

        try {
            return now()->diffInSeconds($timestamp, true) <= $ttl;
        } catch (Throwable) {
            return false;
        }
    }
}
