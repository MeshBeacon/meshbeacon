<?php

namespace App\Http\Controllers;

use App\Jobs\SendSosAck;
use App\Models\IncidentLog;
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
            'clusters'  => collect(),
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

    public function json(): JsonResponse
    {
        return response()->json($this->clusterDataService->getJsonFeed(), 200);
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
        $messageIds = $incidents->pluck('message_id');

        // Auto-open IncidentLog entries for newly-seen SOS events.
        $existing = IncidentLog::whereIn('message_id', $messageIds)
            ->get()->keyBy('message_id');

        foreach ($incidents as $inc) {
            if (!isset($existing[$inc['message_id']])) {
                IncidentLog::firstOrCreate(
                    ['message_id' => $inc['message_id']],
                    [
                        'duck_id'         => $inc['duck_id'],
                        'cluster_data_id' => $inc['id'],
                        'status'          => 'open',
                    ]
                );
            }
        }

        $logs = IncidentLog::whereIn('message_id', $messageIds)
            ->get()->keyBy('message_id');

        $enriched = $incidents->map(function ($inc) use ($logs) {
            $log = $logs[$inc['message_id']] ?? null;
            return array_merge($inc, [
                'incident_log_id'  => $log?->id,
                'incident_status'  => $log?->status ?? 'open',
                'incident_notes'   => $log?->notes,
                'acknowledged_at'  => $log?->acknowledged_at?->diffForHumans(),
                'resolved_at'      => $log?->resolved_at?->diffForHumans(),
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

        IncidentLog::updateOrCreate(
            ['message_id' => $data['message_id']],
            [
                'duck_id'         => $data['duck_id'],
                'status'          => 'acknowledged',
                'acknowledged_at' => now(),
            ]
        );

        return response()->json(['message' => 'ACK sent to ' . $data['duck_id']]);
    }

    public function updateIncidentStatus(Request $request, string $messageId): JsonResponse
    {
        $data = $request->validate([
            'status' => 'required|in:open,acknowledged,responding,resolved',
            'notes'  => 'nullable|string|max:500',
        ]);

        $log = IncidentLog::where('message_id', $messageId)->firstOrFail();

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
}
