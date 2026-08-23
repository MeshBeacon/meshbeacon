<?php

namespace App\Repositories;

use App\Models\ClusterData;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ClusterDataRepository
{
    /**
     * All records, newest first.
     */
    public function getAllOrderedByLatest(): Collection
    {
        return ClusterData::orderBy('id', 'desc')->get();
    }

    /**
     * Single-query aggregate: total messages + distinct PapaDuck (type 1)
     * and MamaDuck (type 2) counts.
     * Uses standard SQL CASE expressions — works on SQLite, MySQL, PostgreSQL.
     */
    public function getStats(): ClusterData
    {
        return ClusterData::selectRaw("
            COUNT(*) as total,
            COUNT(DISTINCT CASE WHEN duck_type = 1 THEN duck_id END) as papaducks,
            COUNT(DISTINCT CASE WHEN duck_type = 2 THEN duck_id END) as mamaducks
        ")->where('topic', '!=', 'gps')->first();
    }

    /**
     * All alert/status/rrep records, newest first (capped at 500 rows for performance).
     * RREP rows are only included when hops > 0 — zero-hop RREPs mean a direct
     * connection (no relay), so they carry no relay-path information.
     */
    public function getAlertStatusOrderedDesc(int $limit = 500): Collection
    {
        return ClusterData::whereIn('topic', ['alert', 'status', 'rrep'])
            ->where(function ($q) {
                $q->where('topic', '!=', 'rrep')
                  ->orWhere('hops', '>', 0);
            })
            ->orderBy('id', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Latest N alert/status records.
     */
    public function getLatestAlertStatus(int $limit): Collection
    {
        return ClusterData::whereIn('topic', ['alert', 'status'])
            ->orderBy('id', 'desc')
            ->take($limit)
            ->get();
    }

    /**
     * Total count of alert/status records.
     */
    public function countAlertStatus(): int
    {
        return ClusterData::whereIn('topic', ['alert', 'status'])->count();
    }

    /**
     * One record per duck_id — the row with the highest id among alert/status
     * topics (excludes MSG_READ receipts and outbound operator messages).
     */
    public function getLatestPerDuck(): Collection
    {
        return ClusterData::whereIn('id', function ($query) {
            $query->selectRaw('max(id)')
                ->from('cluster_data')
                ->whereIn('topic', ['status', 'alert'])
                ->groupBy('duck_id');
        })->get();
    }

    /**
     * All records matching the given topics, newest first.
     */
    public function getAllByTopicsOrderedDesc(array $topics): Collection
    {
        return ClusterData::whereIn('topic', $topics)
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Latest $limit records per duck_id for the given topics.
     * Uses a window function (ROW_NUMBER) — requires SQLite 3.25+, MySQL 8+, or PostgreSQL.
     * Replaces the old pattern of loading all rows then grouping in PHP.
     */
    public function getLatestNPerDuck(array $topics, int $limit): Collection
    {
        $topicsIn = implode(',', array_fill(0, count($topics), '?'));

        $rows = DB::select("
            SELECT * FROM (
                SELECT *, ROW_NUMBER() OVER (PARTITION BY duck_id ORDER BY id DESC) AS rn
                FROM cluster_data
                WHERE topic IN ({$topicsIn})
            ) ranked
            WHERE rn <= ?
            ORDER BY duck_id, id DESC
        ", array_merge($topics, [$limit]));

        return ClusterData::hydrate(
            array_map(fn($row) => (array) $row, $rows)
        );
    }

    /**
     * All records whose created_at falls within the given window.
     * Only the specified columns are fetched (defaults to created_at only
     * which is all that is needed for the hourly chart).
     *
     * @param  string[]  $columns
     */
    /**
     * Most-recent GPS-topic record per duck_id.
     * These are published by the firmware on topic 'gps' (CDP topic 0xEA/234)
     * and may originate from onboard hardware, the companion phone app, or
     * indicate a no-fix condition.
     */
    public function getLatestGpsPerDuck(): Collection
    {
        return ClusterData::whereIn('id', function ($query) {
            $query->selectRaw('max(id)')
                ->from('cluster_data')
                ->where('topic', 'gps')
                ->groupBy('duck_id');
        })->get();
    }

    /**
     * Most recent GPS record PER DUCK that has actual coordinates (LAT/LNG present).
     * Used to surface "Last known coordinates" when the latest record is a no-fix.
     */
    public function getLastKnownCoordsPerDuck(array $duckIds): Collection
    {
        if (empty($duckIds)) {
            return collect();
        }

        return ClusterData::whereIn('id', function ($query) use ($duckIds) {
            $query->selectRaw('max(id)')
                ->from('cluster_data')
                ->where('topic', 'gps')
                ->whereIn('duck_id', $duckIds)
                ->where('payload', 'LIKE', '%LAT:%,LNG:%')
                ->groupBy('duck_id');
        })->get();
    }

    /**
     * Latest record per duck_id that contains coordinates (any topic).
     * Used to populate the dashboard map with last-known positions.
     */
    public function getLatestPositionPerDuck(): Collection
    {
        return ClusterData::whereIn('id', function ($query) {
            $query->selectRaw('max(id)')
                ->from('cluster_data')
                ->where('payload', 'LIKE', '%LAT:%')
                ->where('payload', 'LIKE', '%LNG:%')
                ->groupBy('duck_id');
        })->get();
    }

    /**
     * Most-recent record per duck (any topic) — for duck online/offline health.
     */
    public function getLatestRecordPerDuck(): Collection
    {
        return ClusterData::whereIn('id', function ($query) {
            $query->selectRaw('max(id)')
                ->from('cluster_data')
                ->groupBy('duck_id');
        })->get(['id', 'duck_id', 'topic', 'duck_type', 'payload', 'created_at']);
    }

    /**
     * Most-recent relayed messages (hops > 0, path set) for the topology panel.
     */
    public function getRecentRelays(int $limit = 15): Collection
    {
        return ClusterData::where('hops', '>', 0)
            ->whereNotNull('path')
            ->orderBy('id', 'desc')
            ->limit($limit)
            ->get(['id', 'duck_id', 'topic', 'path', 'hops', 'created_at']);
    }

    /**
     * Latest SOS record per duck_id within the last $hours hours.
     * Covers both hardware SOS (topic='alert', SRC:DEVICE) and mobile-app SOS
     * (topic='status', payload starts with 'SOS' but no SRC:DEVICE).
     * Used to populate the Active Incidents panel on the dashboard.
     */
    public function getActiveIncidents(int $hours = 24): Collection
    {
        return ClusterData::whereIn('id', function ($query) use ($hours) {
            $query->selectRaw('max(id)')
                ->from('cluster_data')
                ->where(function ($q) {
                    // Hardware SOS (physical button)
                    $q->where('topic', 'alert')
                      // Mobile-app SOS (phone sends CDK:SOS → firmware relays as status topic)
                      ->orWhere(function ($q2) {
                          $q2->where('topic', 'status')
                             ->where('payload', 'like', 'SOS,%');
                      });
                })
                ->where('created_at', '>=', now()->subHours($hours))
                ->groupBy('duck_id');
        })->orderByDesc('id')->get();
    }

    /**
     * Records with battery or RSSI telemetry within the last $hours hours,
     * optionally restricted to a single duck_id. Oldest first, capped at
     * $limit rows for performance. Backs the Dashboard's Trends charts.
     */
    public function getTrendsRecords(int $hours, ?string $duckId = null, int $limit = 3000): Collection
    {
        $query = ClusterData::where(function ($q) {
                $q->where('payload', 'like', '%BATT:%')
                  ->orWhere('payload', 'like', '%RSSI:%');
            })
            ->where('created_at', '>=', now()->subHours($hours));

        if ($duckId) {
            $query->where('duck_id', $duckId);
        }

        // NOTE: gps_batt/gps_rssi are Eloquent accessors that parse BATT:/RSSI:
        // out of the payload column at runtime — they aren't real DB columns,
        // so payload (not the accessor names) must be selected here for them
        // to have anything to parse.
        return $query->orderByDesc('id')
            ->limit($limit)
            ->get(['duck_id', 'payload', 'created_at'])
            ->reverse()
            ->values();
    }

    public function getAllInWindow(
        CarbonInterface $start,
        CarbonInterface $end,
        array $columns = ['created_at']
    ): Collection {
        return ClusterData::where('created_at', '>=', $start)
            ->where('created_at', '<=', $end)
            ->where('topic', '!=', 'gps')
            ->get($columns);
    }

    /**
     * Last $limit GPS-topic records for a single duck, oldest first —
     * used to build the history/replay track and battery trend.
     */
    public function getGpsHistoryForDuck(string $duckId, int $limit = 50): Collection
    {
        return ClusterData::where('duck_id', $duckId)
            ->where('topic', 'gps')
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->sortBy('id')
            ->values();
    }

    /**
     * Message counts grouped by duck_id / duck_type / topic within a date range.
     * Used for the reporting page's "message volume by device/type" breakdown.
     */
    public function getMessageVolumeByDuck(CarbonInterface $from, CarbonInterface $to): Collection
    {
        return ClusterData::selectRaw('duck_id, duck_type, topic, COUNT(*) as count')
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('duck_id', 'duck_type', 'topic')
            ->orderBy('duck_id')
            ->get();
    }

    /**
     * Hop-count histogram within a date range — used for relay reliability
     * reporting (proportion of messages that required relaying vs direct).
     */
    public function getHopDistribution(CarbonInterface $from, CarbonInterface $to): Collection
    {
        return ClusterData::selectRaw('hops, COUNT(*) as count')
            ->whereBetween('created_at', [$from, $to])
            ->whereNotNull('hops')
            ->groupBy('hops')
            ->orderBy('hops')
            ->get();
    }

    /**
     * All cluster_data rows sharing a message_id — the full relay/timeline
     * trail for a single incident, oldest first.
     */
    public function getByMessageId(string $messageId): Collection
    {
        return ClusterData::where('message_id', $messageId)
            ->orderBy('id')
            ->get();
    }
}
