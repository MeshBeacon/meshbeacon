<?php

namespace App\Services;

use App\Models\ClusterData;
use App\Repositories\ClusterDataRepository;
use Illuminate\Support\Collection;

class ClusterDataService
{
    public function __construct(
        private readonly ClusterDataRepository $repository
    ) {}

    /**
     * All records, newest first — used to seed the dashboard DataTable on
     * first load before the AJAX feed takes over.
     */
    public function getAllOrderedByLatest(): Collection
    {
        return $this->repository->getAllOrderedByLatest();
    }

    /**
     * Summary counts for the dashboard stat cards.
     *
     * @return array{count: int, papaducks: int, mamaducks: int}
     */
    public function getDashboardStats(): array
    {
        $row = $this->repository->getStats();

        return [
            'count'     => (int) $row->total,
            'papaducks' => (int) $row->papaducks,
            'mamaducks' => (int) $row->mamaducks,
        ];
    }

    /**
     * Enriched alert/status records for the DataTable JSON feed.
     * Business logic: appends computed attributes that are not stored in the DB.
     *
     * @return array{data: Collection, totalCount: int}
     */
    public function getJsonFeed(): array
    {
        $clusters = $this->repository->getAlertStatusOrderedDesc()
            ->map(function (ClusterData $cluster) {
                $urgency = $cluster->urgency;

                return array_merge($cluster->toArray(), [
                    'display_text'  => $cluster->display_text,
                    'urgency_value' => $urgency?->value,
                    'urgency_label' => $urgency?->label(),
                    'map_embed_url' => $cluster->map_embed_url,
                ]);
            });

        return ['data' => $clusters, 'totalCount' => $clusters->count()];
    }

    /**
     * Latest 4 alert/status records + total count for the dashboard feed panel.
     *
     * @return array{data: Collection, totalCount: int}
     */
    public function getTimeline(): array
    {
        return [
            'data'       => $this->repository->getLatestAlertStatus(4),
            'totalCount' => $this->repository->countAlertStatus(),
        ];
    }

    /**
     * Most-recent record per duck_id (alert/status topics only).
     */
    public function getLatestPerDuck(): Collection
    {
        return $this->repository->getLatestPerDuck();
    }

    /**
     * Business logic: from the given collection of duck records return the id
     * of the one most recently seen with GPS coordinates.
     */
    public function latestWithCoordsId(Collection $ducks): ?int
    {
        return $ducks
            ->filter(fn(ClusterData $d) => $d->map_url !== null)
            ->sortByDesc('created_at')
            ->first()
            ?->id;
    }

    /**
     * The last N messages per duck_id, shaped for the history API response.
     * Business logic: groups raw records, determines direction, and formats shape.
     */
    public function getRecentMessagesPerDuck(int $limit = 5): Collection
    {
        return $this->repository
            ->getLatestNPerDuck(['status', 'alert', 'outbound', 'dcmd'], $limit)
            ->groupBy('duck_id')
            ->map(fn($rows) => $rows->map(fn(ClusterData $row) => [
                'id'         => $row->id,
                'message_id' => $row->message_id,
                'topic'      => $row->topic,
                'payload'    => $row->payload,
                'text'       => $row->display_text,
                'map_url'    => $row->map_url,
                'created_at' => $row->created_at,
                'direction'  => $row->topic === 'outbound' ? 'outbound' : 'inbound',
            ])->values());
    }

    /**
     * Last known GPS coordinates per duck_id.
     * Business logic: parses LAT/LNG from the payload string.
     */
    public function lastKnownCoordsPerDuck(): Collection
    {
        return $this->repository
            ->getAllByTopicsOrderedDesc(['status', 'alert', 'outbound', 'dcmd'])
            ->filter(fn(ClusterData $d) => $d->map_url !== null)
            ->groupBy('duck_id')
            ->map(function (Collection $rows) {
                $first = $rows->first();
                $lat   = null;
                $lng   = null;

                if (preg_match(
                    '/LAT:(-?\d+(?:\.\d+)?),LNG:(-?\d+(?:\.\d+)?)/',
                    $first->payload ?? '',
                    $m
                )) {
                    $lat = $m[1];
                    $lng = $m[2];
                }

                return [
                    'map_url'          => $first->map_url,
                    'map_embed_url'    => $first->map_embed_url,
                    'created_at'       => $first->created_at,
                    'lat'              => $lat,
                    'lng'              => $lng,
                    'gps_source_label' => $first->gps_source_label,
                    'gps_from_phone'   => $first->gps_from_phone,
                    'gps_sats'         => $first->gps_sats,
                    'gps_alt'          => $first->gps_alt,
                    'gps_spd'          => $first->gps_spd,
                    'gps_hdg'          => $first->gps_hdg,
                ];
            });
    }

    /**
     * Active incidents feed: latest alert per duck from the past 24 hours.
     * Extracts the nearest relay (first hop after the victim) from the path.
     */
    public function getIncidentsFeed(): array
    {
        $incidents = $this->repository->getActiveIncidents()
            ->map(function (ClusterData $cluster) {
                $pathHops     = $cluster->path ? array_map('trim', explode(',', $cluster->path)) : [];
                $nearestRelay = count($pathHops) >= 2 ? $pathHops[1] : null;
                $urgency      = $cluster->urgency;

                return [
                    'id'              => $cluster->id,
                    'duck_id'         => $cluster->duck_id,
                    'message_id'      => $cluster->message_id,
                    'path'            => $cluster->path,
                    'hops'            => $cluster->hops,
                    'origin'          => $cluster->origin,
                    'destination'     => $cluster->destination,
                    'nearest_relay'   => $nearestRelay,
                    'display_text'    => $cluster->display_text,
                    'payload'         => $cluster->payload,
                    'urgency_value'   => $urgency?->value,
                    'urgency_label'   => $urgency?->label(),
                    'sos_from_device' => $cluster->sos_from_device,
                    'sos_from_mobile' => $cluster->sos_from_mobile,
                    'map_url'         => $cluster->map_url,
                    'created_at'      => $cluster->created_at,
                ];
            });

        return ['data' => $incidents, 'total' => $incidents->count()];
    }

    /**
     * Message counts for each of the past 12 hours (may overlap yesterday),
     * plus a trend comparing the current slot to the previous one.
     *
     * Business logic: all slot bucketing and trend calculation is done in PHP
     * so the repository stays driver-agnostic.
     *
     * @return array{labels: string[], data: int[], trend: array{direction: string, percentage: float, current_hour: int, previous_hour: int}}
     */
    /**
     * Latest GPS-topic record per duck — for the GPS tracking page.
     */
    public function getLatestGpsPerDuck(): Collection
    {
        return $this->repository->getLatestGpsPerDuck();
    }

    /**
     * Most recent GPS record with valid coordinates for the given duck IDs.
     * Keyed by duck_id.
     */
    public function getLastKnownCoordsPerDuck(array $duckIds): Collection
    {
        return $this->repository->getLastKnownCoordsPerDuck($duckIds)->keyBy('duck_id');
    }

    /**
     * Latest position per duck from ANY topic that has LAT/LNG in the payload.
     * Used to populate the dashboard map pins.
     *
     * @return array<string, array{duck_id: string, lat: float, lng: float, topic: string, created_at: string}>
     */
    public function getMapPins(): array
    {
        return $this->repository->getLatestPositionPerDuck()
            ->filter(fn (ClusterData $r) => $r->gps_lat !== null && $r->gps_lng !== null)
            ->map(fn (ClusterData $r) => [
                'duck_id'    => $r->duck_id,
                'lat'        => (float) $r->gps_lat,
                'lng'        => (float) $r->gps_lng,
                'topic'      => $r->topic,
                'source'     => $r->gps_from_phone ? 'Phone' : 'Satellite',
                'created_at' => $r->created_at->diffForHumans(),
                'map_url'    => 'https://www.google.com/maps?q=' . $r->gps_lat . ',' . $r->gps_lng,
            ])
            ->values()
            ->toArray();
    }

    /**
     * Online/idle/offline status + battery per duck for the health widget.
     * online  = last message < 10 minutes ago
     * idle    = 10–60 minutes ago
     * offline = > 60 minutes ago
     */
    public function getDuckHealth(): array
    {
        $onlineThreshold = now()->subMinutes(10);
        $idleThreshold   = now()->subMinutes(60);

        return $this->repository->getLatestRecordPerDuck()
            ->map(fn (ClusterData $r) => [
                'duck_id'   => $r->duck_id,
                'duck_type' => (int) $r->duck_type,
                'status'    => $r->created_at->greaterThan($onlineThreshold) ? 'online'
                             : ($r->created_at->greaterThan($idleThreshold)  ? 'idle' : 'offline'),
                'last_seen' => $r->created_at->diffForHumans(),
                'battery'   => $r->gps_batt,
            ])
            ->sortBy('duck_id')
            ->values()
            ->toArray();
    }

    /**
     * Recent relay paths for the topology panel.
     */
    public function getTopology(): array
    {
        return $this->repository->getRecentRelays(15)
            ->map(fn (ClusterData $r) => [
                'duck_id'    => $r->duck_id,
                'topic'      => $r->topic,
                'path'       => $r->path,
                'hops'       => $r->hops,
                'created_at' => $r->created_at->diffForHumans(),
            ])
            ->values()
            ->toArray();
    }

    public function getHourlyMessageCounts(): array
    {
        $windowStart = now()->subHours(11)->startOfHour();
        $windowEnd   = now()->endOfHour();

        $records = $this->repository->getAllInWindow($windowStart, $windowEnd);

        $labels = [];
        $data   = [];

        for ($i = 0; $i < 12; $i++) {
            $slotStart = $windowStart->copy()->addHours($i);
            $slotEnd   = $slotStart->copy()->endOfHour();

            $labels[] = $slotStart->format('H:i');
            $data[]   = $records->filter(
                fn($r) => $r->created_at->between($slotStart, $slotEnd)
            )->count();
        }

        // Trend: slot 11 (current, possibly incomplete) vs slot 10 (last full hour).
        $currentCount  = $data[11] ?? 0;
        $previousCount = $data[10] ?? 0;

        $direction  = $currentCount >= $previousCount ? 'up' : 'down';
        $percentage = $previousCount > 0
            ? round(abs(($currentCount - $previousCount) / $previousCount) * 100, 1)
            : ($currentCount > 0 ? 100.0 : 0.0);

        return [
            'labels' => $labels,
            'data'   => $data,
            'trend'  => [
                'direction'     => $direction,
                'percentage'    => $percentage,
                'current_hour'  => $currentCount,
                'previous_hour' => $previousCount,
            ],
        ];
    }

    /**
     * Merged per-duck history payload used by /status/history.
     * Business logic: joins messages + coords, computes last-seen status.
     *
     * @return Collection<string, array>
     */
    public function buildHistoryResponse(): Collection
    {
        $messages   = $this->getRecentMessagesPerDuck(50);
        $lastCoords = $this->lastKnownCoordsPerDuck();
        $allDucks   = $messages->keys()->merge($lastCoords->keys())->unique();

        return $allDucks->mapWithKeys(function ($duckId) use ($messages, $lastCoords) {
            $duckMessages  = $messages->get($duckId, collect());
            $latestMessage = $duckMessages->first();

            // Detect old devices: latest inbound SOS has SRC:DEVICE but no LAT: field at all.
            $latestInbound = $duckMessages->first(
                fn($m) => $m['direction'] !== 'outbound'
                    && !preg_match('/^MSG_READ\b/i', $m['payload'] ?? '')
            );
            $latestPayload    = $latestInbound['payload'] ?? '';
            $gpsHardwareAbsent = (bool) preg_match('/\bSOS\b/i', $latestPayload)
                && (bool) preg_match('/\bSRC:DEVICE\b/i', $latestPayload)
                && !(bool) preg_match('/\bLAT:/i', $latestPayload);
            $gpsUnavailable = (bool) preg_match('/LAT:none|LNG:none/i', $latestPayload);

            return [$duckId => [
                'messages'            => $duckMessages->values(),
                'last_seen'           => $latestMessage ? [
                    'created_at'            => $latestMessage['created_at'],
                    'created_at_for_humans' => $latestMessage['created_at']->diffForHumans(),
                    'is_online'             => $latestMessage['created_at']->gt(now()->subHour()),
                ] : null,
                'last_coords'         => $lastCoords->has($duckId) ? [
                    'map_url'               => $lastCoords[$duckId]['map_url'],
                    'map_embed_url'         => $lastCoords[$duckId]['map_embed_url'],
                    'created_at'            => $lastCoords[$duckId]['created_at'],
                    'created_at_for_humans' => $lastCoords[$duckId]['created_at']->diffForHumans(),
                    'lat'                   => $lastCoords[$duckId]['lat'],
                    'lng'                   => $lastCoords[$duckId]['lng'],
                    'gps_source_label'      => $lastCoords[$duckId]['gps_source_label'],
                    'gps_from_phone'        => $lastCoords[$duckId]['gps_from_phone'],
                    'gps_sats'              => $lastCoords[$duckId]['gps_sats'],
                    'gps_alt'               => $lastCoords[$duckId]['gps_alt'],
                    'gps_spd'               => $lastCoords[$duckId]['gps_spd'],
                    'gps_hdg'               => $lastCoords[$duckId]['gps_hdg'],
                ] : null,
                'gps_hardware_absent' => $gpsHardwareAbsent,
                'gps_unavailable'     => $gpsUnavailable,
            ]];
        });
    }

    /**
     * GPS history/replay track for a single duck: oldest-first list of
     * coordinates + battery readings, for drawing a polyline and a
     * battery trend on the GPS tracking page.
     *
     * @return array<int, array>
     */
    public function getGpsHistory(string $duckId, int $limit = 50): array
    {
        return $this->repository->getGpsHistoryForDuck($duckId, $limit)
            ->filter(fn (ClusterData $r) => $r->gps_lat !== null && $r->gps_lng !== null)
            ->map(fn (ClusterData $r) => [
                'lat'        => (float) $r->gps_lat,
                'lng'        => (float) $r->gps_lng,
                'batt'       => $r->gps_batt,
                'spd'        => $r->gps_spd,
                'source'     => $r->gps_from_phone ? 'Phone' : 'Satellite',
                'created_at' => $r->created_at->toJSON(),
                'label'      => $r->created_at->format('j M, H:i'),
            ])
            ->values()
            ->toArray();
    }
}
