<?php

namespace App\Services;

use App\Models\IncidentLog;
use App\Repositories\ClusterDataRepository;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class ReportService
{
    private const DUCK_TYPE_LABELS = [1 => 'PapaDuck', 2 => 'MamaDuck'];

    public function __construct(
        private readonly ClusterDataRepository $repository
    ) {}

    /**
     * Full analytics summary for the reporting page: message volume by
     * device/type, relay reliability (hop distribution), and SOS
     * response-time analytics — all scoped to a date range.
     */
    public function getSummary(CarbonInterface $from, CarbonInterface $to): array
    {
        $incidents = IncidentLog::whereBetween('created_at', [$from, $to])->get();

        return [
            'range'            => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'message_volume'   => $this->buildMessageVolume($this->repository->getMessageVolumeByDuck($from, $to)),
            'hop_distribution' => $this->buildHopDistribution($this->repository->getHopDistribution($from, $to)),
            'sos_analytics'    => $this->buildSosAnalytics($incidents),
        ];
    }

    /**
     * IncidentLog rows (with responder) created within the date range,
     * newest first — the raw material for the incidents table + period export.
     *
     * When $search is given, matches it (case-insensitively) against the
     * duck ID, notes, and assigned responder's name — a single combined
     * keyword search rather than separate per-field filters, since
     * operators typically search for "everything about X" rather than a
     * specific field.
     */
    public function getIncidentsInRange(CarbonInterface $from, CarbonInterface $to, ?string $search = null): Collection
    {
        $query = IncidentLog::with('assignedTo:id,name')
            ->whereBetween('created_at', [$from, $to]);

        if ($search !== null && $search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('duck_id', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('assignedTo', fn ($u) => $u->where('name', 'like', "%{$search}%"));
            });
        }

        return $query->orderByDesc('created_at')->get();
    }

    /**
     * Full message/relay timeline for a single incident (all cluster_data
     * rows sharing its message_id), oldest first.
     */
    public function getIncidentTimeline(string $messageId): Collection
    {
        return $this->repository->getByMessageId($messageId);
    }

    private function buildMessageVolume(Collection $rows): array
    {
        $byDuck     = [];
        $byTopic    = [];
        $byDuckType = [];

        foreach ($rows as $row) {
            $duckId = $row->duck_id;
            $type   = (int) $row->duck_type;
            $label  = self::DUCK_TYPE_LABELS[$type] ?? 'Unknown';
            $count  = (int) $row->count;

            $byDuck[$duckId] ??= ['duck_id' => $duckId, 'duck_type' => $type, 'label' => $label, 'total' => 0];
            $byDuck[$duckId]['total'] += $count;

            $byTopic[$row->topic] = ($byTopic[$row->topic] ?? 0) + $count;
            $byDuckType[$label]   = ($byDuckType[$label] ?? 0) + $count;
        }

        $byDuck = array_values($byDuck);
        usort($byDuck, fn ($a, $b) => $b['total'] <=> $a['total']);

        return [
            'total'        => array_sum($byTopic),
            'by_duck'      => $byDuck,
            'by_topic'     => $byTopic,
            'by_duck_type' => $byDuckType,
        ];
    }

    private function buildHopDistribution(Collection $rows): array
    {
        $labels          = [];
        $data            = [];
        $totalMessages   = 0;
        $relayedMessages = 0;
        $hopSum          = 0;

        foreach ($rows as $row) {
            $hops  = (int) $row->hops;
            $count = (int) $row->count;

            $labels[] = $hops === 0 ? 'Direct' : $hops . ' hop' . ($hops > 1 ? 's' : '');
            $data[]   = $count;

            $totalMessages += $count;
            $hopSum        += $hops * $count;
            if ($hops > 0) {
                $relayedMessages += $count;
            }
        }

        return [
            'labels'                => $labels,
            'data'                  => $data,
            'total_messages'        => $totalMessages,
            'relayed_messages'      => $relayedMessages,
            'relay_reliability_pct' => $totalMessages > 0 ? round(($relayedMessages / $totalMessages) * 100, 1) : 0.0,
            'avg_hops'              => $totalMessages > 0 ? round($hopSum / $totalMessages, 2) : 0.0,
        ];
    }

    private function buildSosAnalytics(Collection $incidents): array
    {
        $ackTimes = $incidents->filter(fn ($l) => $l->acknowledged_at)
            ->map(fn ($l) => $l->created_at->diffInSeconds($l->acknowledged_at));

        $resolveTimes = $incidents->filter(fn ($l) => $l->resolved_at && $l->acknowledged_at)
            ->map(fn ($l) => $l->acknowledged_at->diffInSeconds($l->resolved_at));

        return [
            'total_incidents'        => $incidents->count(),
            'open_incidents'         => $incidents->where('status', 'open')->count(),
            'acknowledged_incidents' => $incidents->where('status', 'acknowledged')->count(),
            'responding_incidents'   => $incidents->where('status', 'responding')->count(),
            'resolved_incidents'     => $incidents->where('status', 'resolved')->count(),
            'avg_ack_seconds'        => $ackTimes->isNotEmpty() ? (int) round($ackTimes->avg()) : null,
            'min_ack_seconds'        => $ackTimes->isNotEmpty() ? (int) $ackTimes->min() : null,
            'max_ack_seconds'        => $ackTimes->isNotEmpty() ? (int) $ackTimes->max() : null,
            'avg_resolve_seconds'    => $resolveTimes->isNotEmpty() ? (int) round($resolveTimes->avg()) : null,
        ];
    }
}
