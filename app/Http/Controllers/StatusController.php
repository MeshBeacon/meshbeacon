<?php

namespace App\Http\Controllers;

use App\Models\ClusterData;
use App\Models\GpsPoll;
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

        return view('status', compact('mamaducks', 'latestCoordsId'));
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
            'duck_type'  => 'operator',
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
        $duckId = $request->validate(['duck_id' => 'required|string'])['duck_id'];

        $poll = GpsPoll::firstOrCreate(
            ['duck_id' => $duckId],
            ['enabled' => false, 'next_run_at' => null]
        );

        $poll->enabled = ! $poll->enabled;

        if ($poll->enabled) {
            $poll->next_run_at = Carbon::now()->addMinutes(\App\Console\Commands\PollGps::INTERVAL_MINUTES);
        } else {
            $poll->next_run_at = null;
        }

        $poll->save();

        return response()->json([
            'enabled'     => $poll->enabled,
            'next_run_at' => $poll->next_run_at?->toJSON() ?? null,
        ]);
    }

    public function message(Request $request): JsonResponse
    {
        $message = $request->input('message');
        $duckId  = $request->input('duck_id');

        $this->mqttService->sendCommand(
            message: $message,
            target:  $duckId,
        );

        // Persist the operator-sent message so it appears in history
        // and can be matched against MSG_READ receipts from the duck.
        ClusterData::create([
            'duck_id'    => $duckId,
            'topic'      => 'outbound',
            'message_id' => uniqid('OUT-'),
            'payload'    => 'MSG,TEXT:' . $message,
            'hops'       => 0,
            'duck_type'  => 'operator',
        ]);

        return response()->json(['message' => 'Form submitted successfully!']);
    }
}
