<?php

namespace App\Http\Controllers;

use App\Models\ClusterData;
use App\Models\GpsPoll;
use App\Models\IncidentLog;
use App\Services\ClusterDataService;
use App\Services\MqttService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class StatusController extends Controller
{
    public function __construct(
        private ClusterDataService $clusterDataService,
        private MqttService $mqttService,
    ) {}

    public function index()
    {
        $mamaducks      = $this->clusterDataService->getLatestPerDuck();
        $latestCoordsId = $this->clusterDataService->latestWithCoordsId($mamaducks);

        // A duck can accumulate multiple historical IncidentLog rows, so
        // filtering to non-resolved rows first (then keying by duck_id) can
        // surface a stale open/acknowledged row while ignoring a newer,
        // already-resolved one for the same duck — showing the badge as
        // active when the duck's true latest incident has been resolved.
        // Instead, pick each duck's single most recent log first, then check
        // ITS status — mirroring DashboardController::incidents(), which
        // must agree with this query or the badge can flash open before the
        // JS poll (using that endpoint) corrects it moments later.
        $activeIncidents = IncidentLog::whereIn('duck_id', $mamaducks->pluck('duck_id'))
            ->orderByDesc('id')
            ->get()
            ->unique('duck_id')
            ->reject(fn ($log) => $log->status === 'resolved')
            ->keyBy('duck_id');

        return view('status', compact('mamaducks', 'latestCoordsId', 'activeIncidents'));
    }

    public function gps()
    {
        $gpsRecords = $this->clusterDataService->getLatestGpsPerDuck();
        $pollStates = GpsPoll::whereIn('duck_id', $gpsRecords->pluck('duck_id'))
                             ->get()
                             ->keyBy('duck_id');

        $noFixDuckIds    = $gpsRecords->filter(fn ($r) => $r->gps_fix_zero)->pluck('duck_id')->all();
        $lastKnownCoords = $this->clusterDataService->getLastKnownCoordsPerDuck($noFixDuckIds);

        return view('gps', compact('gpsRecords', 'pollStates', 'lastKnownCoords'));
    }

    public function gpsJson(): JsonResponse
    {
        $records = $this->clusterDataService->getLatestGpsPerDuck();
        $polls = GpsPoll::whereIn('duck_id', $records->pluck('duck_id'))
                         ->get()
                         ->keyBy('duck_id');

        $noFixDuckIds    = $records->filter(fn ($r) => $r->gps_fix_zero)->pluck('duck_id')->all();
        $lastKnownCoords = $this->clusterDataService->getLastKnownCoordsPerDuck($noFixDuckIds);

        $data = $records->keyBy('duck_id')->map(fn ($r) => [
            'id'               => $r->id,
            'duck_id'          => $r->duck_id,
            'gps_source_label' => $r->gps_source_label,
            'gps_badge_label'  => $r->gps_badge_label,
            'gps_fix_zero'     => $r->gps_fix_zero,
            'gps_from_phone'   => $r->gps_from_phone,
            'gps_no_phone'     => $r->gps_no_phone,
            'gps_lat'          => $r->gps_lat,
            'gps_lng'          => $r->gps_lng,
            'gps_sats'         => $r->gps_sats,
            'gps_batt'         => $r->gps_batt,
            'gps_alt'          => $r->gps_alt,
            'gps_spd'          => $r->gps_spd,
            'gps_hdg'          => $r->gps_hdg,
            'map_url'          => $r->map_url,
            'map_embed_url'    => $r->map_embed_url,
            'last_known_lat'   => ($lastKnownCoords[$r->duck_id] ?? null)?->gps_lat,
            'last_known_lng'   => ($lastKnownCoords[$r->duck_id] ?? null)?->gps_lng,
            'last_known_alt'   => ($lastKnownCoords[$r->duck_id] ?? null)?->gps_alt,
            'last_known_spd'   => ($lastKnownCoords[$r->duck_id] ?? null)?->gps_spd,
            'last_known_hdg'   => ($lastKnownCoords[$r->duck_id] ?? null)?->gps_hdg,
            'last_known_at'    => ($lastKnownCoords[$r->duck_id] ?? null)?->created_at?->diffForHumans(),
            'created_at_for_humans' => $r->created_at->diffForHumans(),
            'created_at_formatted' => $r->created_at->format('j M Y, H:i'),
            'poll_enabled'     => ($polls[$r->duck_id] ?? null)?->enabled ?? false,
            'poll_next_at'     => ($polls[$r->duck_id] ?? null)?->next_run_at?->toJSON() ?? null,
            'poll_interval_minutes' => ($polls[$r->duck_id] ?? null)?->interval_minutes ?? 1,
        ]);
        return response()->json($data);
    }

    public function history(): JsonResponse
    {
        return response()->json($this->clusterDataService->buildHistoryResponse());
    }

    public function broadcast(Request $request): JsonResponse
    {
        $message = $request->validate(['message' => 'required|string|max:200'])['message'];

        $this->mqttService->sendCommand(
            message: $message,
            target:  'BROADCAST',
            topic:   24,
        );

        ClusterData::create([
            'duck_id'    => 'BROADCAST',
            'topic'      => 'outbound',
            'message_id' => uniqid('BC-'),
            'payload'    => 'MSG,TEXT:' . $message,
            'hops'       => 0,
            'duck_type'  => 0,
        ]);

        return response()->json(['message' => 'Emergency broadcast sent successfully!']);
    }

    public function requestGps(Request $request): JsonResponse
    {
        $duckId = $request->validate(['duck_id' => 'required|string'])['duck_id'];

        $this->mqttService->sendCommand(
            message: 'null',
            target:  $duckId,
            topic:   234,
        );

        return response()->json(['message' => 'GPS request sent to ' . $duckId]);
    }

    public function toggleGpsPoll(Request $request): JsonResponse
    {
        $data = $request->validate([
            'duck_id'          => 'required|string',
            'interval_minutes' => 'nullable|integer|min:1|max:1440',
        ]);

        $poll = GpsPoll::firstOrCreate(
            ['duck_id' => $data['duck_id']],
            ['enabled' => false, 'interval_minutes' => 1, 'next_run_at' => null]
        );

        if (array_key_exists('interval_minutes', $data) && $data['interval_minutes']) {
            $poll->interval_minutes = $data['interval_minutes'];
        }

        $poll->enabled = ! $poll->enabled;

        if ($poll->enabled) {
            $poll->next_run_at = Carbon::now()->addMinutes($poll->interval_minutes);
        } else {
            $poll->next_run_at = null;
        }

        $poll->save();

        return response()->json([
            'enabled'          => $poll->enabled,
            'interval_minutes' => $poll->interval_minutes,
            'next_run_at'      => $poll->next_run_at?->toJSON() ?? null,
        ]);
    }

    /**
     * Set the auto-poll interval for a duck without toggling enabled state.
     */
    public function setGpsPollInterval(Request $request): JsonResponse
    {
        $data = $request->validate([
            'duck_id'          => 'required|string',
            'interval_minutes' => 'required|integer|min:1|max:1440',
        ]);

        $poll = GpsPoll::firstOrCreate(
            ['duck_id' => $data['duck_id']],
            ['enabled' => false, 'interval_minutes' => 1, 'next_run_at' => null]
        );

        $poll->interval_minutes = $data['interval_minutes'];

        if ($poll->enabled) {
            $poll->next_run_at = Carbon::now()->addMinutes($poll->interval_minutes);
        }

        $poll->save();

        return response()->json([
            'enabled'          => $poll->enabled,
            'interval_minutes' => $poll->interval_minutes,
            'next_run_at'      => $poll->next_run_at?->toJSON() ?? null,
        ]);
    }

    /**
     * Last $limit GPS-topic points for a single duck, oldest first, for the
     * history/replay map and battery trend on the GPS tracking page.
     */
    public function gpsHistory(string $duckId): JsonResponse
    {
        $points = $this->clusterDataService->getGpsHistory($duckId, 50);

        return response()->json(['data' => $points]);
    }

    public function message(Request $request): JsonResponse
    {
        $message = $request->input('message');
        $duckId  = $request->input('duck_id');

        // Encrypted via reservedTopic::encrypted_cmd when the Duck's public
        // key is already known and OpenDMS's static keypair is configured;
        // otherwise falls back to plaintext dcmd -- see
        // MqttService::sendEncryptedCommand().
        $this->mqttService->sendEncryptedCommand($message, $duckId);

        // Persist the operator-sent message so it appears in history
        // and can be matched against MSG_READ receipts from the duck.
        ClusterData::create([
            'duck_id'    => $duckId,
            'topic'      => 'outbound',
            'message_id' => uniqid('OUT-'),
            'payload'    => 'MSG,TEXT:' . $message,
            'hops'       => 0,
            'duck_type'  => 0,
        ]);

        return response()->json(['message' => 'Form submitted successfully!']);
    }
}
