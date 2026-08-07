<?php

namespace App\Http\Controllers;

use App\Jobs\SendSosAck;
use App\Models\ClusterData;
use App\Models\IncidentLog;
use App\Models\User;
use App\Services\ClusterDataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private ClusterDataService $clusterDataService)
    {}

    public function index()
    {
        $stats = $this->clusterDataService->getDashboardStats();

        return view('dashboard', [
            'count'     => $stats['count'],
            'papaducks' => $stats['papaducks'],
            'mamaducks' => $stats['mamaducks'],
        ]);
    }

    public function kiosk()
    {
        $stats = $this->clusterDataService->getDashboardStats();

        return view('kiosk', [
            'count'     => $stats['count'],
            'papaducks' => $stats['papaducks'],
            'mamaducks' => $stats['mamaducks'],
        ]);
    }

    public function stats(): JsonResponse
    {
        return response()->json($this->clusterDataService->getDashboardStats(), 200);
    }

    public function mapPins(): JsonResponse
    {
        return response()->json($this->clusterDataService->getMapPins(), 200);
    }

    public function duckHealth(): JsonResponse
    {
        return response()->json($this->clusterDataService->getDuckHealth(), 200);
    }

    public function topology(): JsonResponse
    {
        return response()->json($this->clusterDataService->getTopology(), 200);
    }

    public function timeline(): JsonResponse
    {
        return response()->json($this->clusterDataService->getTimeline(), 200);
    }

    public function hourly(): JsonResponse
    {
        return response()->json($this->clusterDataService->getHourlyMessageCounts(), 200);
    }

    public function incidents(): JsonResponse
    {
        $feed       = $this->clusterDataService->getIncidentsFeed();
        $incidents  = collect($feed['data']);

        // Auto-open/update IncidentLog entries for newly-seen SOS events.
        //
        // The device only transmits once per physical button press (no
        // periodic heartbeat/retransmission of the same event), so a new
        // message_id always means the user pressed SOS again — a distinct,
        // deliberate signal, not network noise. A duck can only ever be in
        // one real emergency at a time, so this is treated as the SAME
        // incident (bump the message/cluster reference, keep assignment and
        // notes intact) rather than spawning a duplicate. But since a fresh
        // press means the person still needs help, the incident is flipped
        // back to 'open' (if it had been 'acknowledged') so responders are
        // re-alerted instead of it silently staying acknowledged.
        //
        // IMPORTANT: this must key off each duck's single most recent log
        // row (highest id) — the SAME row the enrichment step below shows
        // to the UI — never a separate "any non-resolved row for this duck"
        // lookup. If a duck ever ends up with more than one non-resolved
        // row (a data-integrity anomaly the unique index is meant to
        // prevent, but which can still occur, e.g. from a partially-applied
        // migration or a rare insert race), a status-filtered lookup can
        // silently pick a DIFFERENT, hidden row than the one displayed —
        // so every future SOS keeps updating that hidden row while the
        // visible one stays stuck in its last state forever, with no error
        // ever surfacing. Always operating on "this duck's latest row"
        // makes the auto-open logic and the enrichment/display logic
        // provably consistent, and self-healing if a stray duplicate ever
        // exists (the newest row always wins, on both reads and writes).
        $logsByDuck = IncidentLog::whereIn('duck_id', $incidents->pluck('duck_id'))
            ->orderByDesc('id')
            ->get()
            ->unique('duck_id')
            ->keyBy('duck_id');

        foreach ($incidents as $inc) {
            $log = $logsByDuck[$inc['duck_id']] ?? null;

            if (!$log) {
                IncidentLog::create([
                    'message_id'      => $inc['message_id'],
                    'duck_id'         => $inc['duck_id'],
                    'cluster_data_id' => $inc['id'],
                    'status'          => 'open',
                ]);
                continue;
            }

            if ($log->status !== 'resolved') {
                if ($log->message_id !== $inc['message_id']) {
                    $log->update([
                        'message_id'            => $inc['message_id'],
                        'cluster_data_id'        => $inc['id'],
                        'status'                 => 'open',
                        'retransmission_count'   => $log->retransmission_count + 1,
                    ]);
                }
                continue;
            }

            // The duck's latest log is resolved. Only actually reopen if
            // this is a genuinely NEW transmission (a different ClusterData
            // row) than what the resolved log already recorded. The
            // active-incidents feed keeps returning a duck's latest message
            // for up to 24 hours regardless of resolution status, so
            // without this check, the very same stale alert a responder
            // just resolved would flip back to 'open' again on the next
            // poll — even though nothing new was received from the device.
            if ($log->cluster_data_id !== $inc['id']) {
                // Self-heal: a stray non-resolved row for this duck should
                // never exist (it would mean an older row was silently
                // absorbing SOS updates while this newer, resolved row sat
                // ignored — the exact bug this whole method now prevents),
                // but resolve any that are found before reopening, so the
                // "one open row per duck" DB constraint never blocks this
                // update.
                $this->resolveStraySiblings($log);

                $log->update([
                    'cluster_data_id'      => $inc['id'],
                    'status'               => 'open',
                    'notes'                => null,
                    'assigned_to'          => null,
                    'assigned_at'          => null,
                    'acknowledged_at'      => null,
                    'resolved_at'          => null,
                    'retransmission_count' => 1,
                ]);
            }
        }

        // Enrich by duck_id, not message_id: message_id can be reused across
        // a duck's history (see reopen logic above), so a duck's CURRENT
        // incident is always its most recently created/updated log — never
        // determined by matching the latest SOS message_id, which could
        // collide with an older, unrelated (and already-resolved) log.
        $logs = IncidentLog::with('assignedTo:id,name')
            ->whereIn('duck_id', $incidents->pluck('duck_id'))
            ->orderByDesc('id')
            ->get()
            ->unique('duck_id')
            ->keyBy('duck_id');

        $enriched = $incidents->map(function ($inc) use ($logs) {
            $log = $logs[$inc['duck_id']] ?? null;
            return array_merge($inc, [
                'incident_log_id'      => $log?->id,
                'incident_status'      => $log?->status ?? 'open',
                'incident_notes'       => $log?->notes,
                'assigned_to'          => $log?->assigned_to,
                'assigned_to_name'     => $log?->assignedTo?->name,
                'acknowledged_at'      => $log?->acknowledged_at?->diffForHumans(),
                'resolved_at'          => $log?->resolved_at?->diffForHumans(),
                'retransmission_count' => $log?->retransmission_count ?? 1,
            ]);
        })->values();

        return response()->json(['data' => $enriched, 'total' => $enriched->count()], 200);
    }

    public function sosAck(Request $request): JsonResponse
    {
        $data = $request->validate([
            'duck_id'    => 'required|string|max:64',
            'message_id' => 'required|string|max:64',
        ]);

        SendSosAck::dispatch($data['duck_id'], 1);

        // Resolve the duck's current incident (its single most recent log
        // row, matching what incidents()/the UI displays) rather than an
        // exact message_id match, so an SOS retransmission that arrived
        // after the page last polled doesn't spawn a duplicate incident.
        // Using duck_id's latest row — not a "status != resolved" filter —
        // avoids ever silently acknowledging a different, hidden row than
        // the one the operator is actually looking at.
        $log = IncidentLog::where('duck_id', $data['duck_id'])
            ->orderByDesc('id')
            ->first();

        if ($log) {
            // See resolveStraySiblings(): a leftover non-resolved row for
            // this duck (from before this canonical-lookup fix, or any
            // other data-integrity anomaly) would otherwise collide with
            // the "one open row per duck" unique constraint the instant
            // this row is touched, since it's staying/becoming non-resolved.
            $this->resolveStraySiblings($log);

            $log->update([
                'message_id'      => $data['message_id'],
                'status'          => 'acknowledged',
                'acknowledged_at' => $log->acknowledged_at ?? now(),
            ]);
        } else {
            IncidentLog::create([
                'duck_id'         => $data['duck_id'],
                'message_id'      => $data['message_id'],
                'status'          => 'acknowledged',
                'acknowledged_at' => now(),
            ]);
        }

        return response()->json(['message' => 'ACK sent to ' . $data['duck_id']]);
    }

    /**
     * Resolve the IncidentLog referenced by a message_id. Falls back to the
     * originating duck's current open incident if that exact message_id was
     * already superseded by a newer SOS retransmission before this request
     * arrived.
     */
    private function resolveIncidentLog(string $messageId): IncidentLog
    {
        $log = IncidentLog::where('message_id', $messageId)->first();
        if ($log) {
            return $log;
        }

        $duckId = ClusterData::where('message_id', $messageId)->value('duck_id');

        // Fall back to the duck's single most recent log row — the same
        // one incidents()/the UI displays — rather than any row merely
        // matching "status != resolved", which could silently resolve to a
        // different, hidden row if a duck ever ends up with more than one
        // non-resolved log.
        return IncidentLog::where('duck_id', $duckId)
            ->orderByDesc('id')
            ->firstOrFail();
    }

    /**
     * Resolve any OTHER non-resolved IncidentLog rows sharing the same
     * duck_id as $log. At most one non-resolved row per duck should ever
     * exist (enforced by a DB-level unique constraint on MySQL/MariaDB via
     * a generated `open_duck_id` column, and a true partial unique index on
     * SQLite/Postgres), but a stray extra row can still occur from data
     * predating this invariant, a partially-applied migration, or a rare
     * insert race. Left alone, such a row silently absorbs future updates
     * meant for the duck's real (displayed) incident, AND makes any update
     * that keeps/sets $log non-resolved throw a
     * UniqueConstraintViolationException. Call this before any such update.
     */
    private function resolveStraySiblings(IncidentLog $log): void
    {
        IncidentLog::where('duck_id', $log->duck_id)
            ->where('id', '!=', $log->id)
            ->where('status', '!=', 'resolved')
            ->update(['status' => 'resolved', 'resolved_at' => now()]);
    }

    public function updateIncidentStatus(Request $request, string $messageId): JsonResponse
    {
        $data = $request->validate([
            'status' => 'required|in:open,acknowledged,responding,resolved',
            'notes'  => 'nullable|string|max:500',
        ]);

        $log = $this->resolveIncidentLog($messageId);

        if ($data['status'] !== 'resolved') {
            $this->resolveStraySiblings($log);
        }

        $update = ['status' => $data['status']];
        if (array_key_exists('notes', $data)) {
            $update['notes'] = $data['notes'];
        }
        if ($data['status'] === 'acknowledged' && !$log->acknowledged_at) {
            $update['acknowledged_at'] = now();
        }
        if ($data['status'] === 'resolved' && !$log->resolved_at) {
            $update['resolved_at'] = now();
        }

        $log->update($update);

        return response()->json(['message' => 'Status updated', 'status' => $data['status']]);
    }

    /**
     * Update only the notes for an incident, without changing its status.
     */
    public function updateIncidentNotes(Request $request, string $messageId): JsonResponse
    {
        $data = $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        $log = $this->resolveIncidentLog($messageId);
        $log->update(['notes' => $data['notes']]);

        return response()->json(['message' => 'Notes updated']);
    }

    /**
     * Assign an incident to a responder (or unassign with a null user_id).
     */
    public function assignIncident(Request $request, string $messageId): JsonResponse
    {
        $data = $request->validate([
            'user_id' => 'nullable|exists:users,id',
        ]);

        $log = $this->resolveIncidentLog($messageId);
        $log->update([
            'assigned_to' => $data['user_id'] ?? null,
            'assigned_at' => $data['user_id'] ? now() : null,
        ]);

        return response()->json([
            'message'      => $data['user_id'] ? 'Incident assigned' : 'Incident unassigned',
            'assigned_to'  => $log->assigned_to,
        ]);
    }

    /**
     * Acknowledge every currently-open incident in one action, re-sending
     * the SOS ACK to each affected duck.
     */
    public function bulkAcknowledgeIncidents(): JsonResponse
    {
        $openLogs = IncidentLog::where('status', 'open')->get();

        foreach ($openLogs as $log) {
            $log->update([
                'status'          => 'acknowledged',
                'acknowledged_at' => now(),
            ]);
            SendSosAck::dispatch($log->duck_id, 1);
        }

        return response()->json([
            'message' => 'Acknowledged ' . $openLogs->count() . ' incident(s)',
            'count'   => $openLogs->count(),
        ]);
    }

    /**
     * List of users selectable as incident responders.
     *
     * Admins are excluded: the `admin` role represents system/user
     * management privileges, not field/duty responders. Incidents should
     * only be assignable to operators, the users actually expected to
     * act on them.
     */
    public function responders(): JsonResponse
    {
        return response()->json(
            User::where('role', User::ROLE_OPERATOR)
                ->orderBy('name')
                ->get(['id', 'name'])
        );
    }

    /**
     * Response-time SLA metrics: average time-to-acknowledge and
     * time-to-resolve over incidents from the past 7 days.
     */
    public function incidentStats(): JsonResponse
    {
        $since = now()->subDays(7);

        $recent = IncidentLog::where('created_at', '>=', $since)->get();

        $ackTimes = $recent->filter(fn ($l) => $l->acknowledged_at)
            ->map(fn ($l) => $l->created_at->diffInSeconds($l->acknowledged_at));

        $resolveTimes = $recent->filter(fn ($l) => $l->resolved_at && $l->acknowledged_at)
            ->map(fn ($l) => $l->acknowledged_at->diffInSeconds($l->resolved_at));

        return response()->json([
            'avg_ack_seconds'     => $ackTimes->isNotEmpty() ? (int) round($ackTimes->avg()) : null,
            'avg_resolve_seconds' => $resolveTimes->isNotEmpty() ? (int) round($resolveTimes->avg()) : null,
            'total_incidents'     => $recent->count(),
            'open_incidents'      => $recent->where('status', 'open')->count(),
            'resolved_incidents'  => $recent->where('status', 'resolved')->count(),
        ]);
    }
}
