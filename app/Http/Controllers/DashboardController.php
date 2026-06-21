<?php

namespace App\Http\Controllers;

use App\Services\ClusterDataService;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __construct(private ClusterDataService $clusterDataService)
    {}

    public function index()
    {
        $stats = $this->clusterDataService->getDashboardStats();

        // The DataTable is populated entirely via AJAX (/dashboard/json),
        // so we pass an empty collection — rendering all rows in Blade first
        // would be redundant and slow.
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
        return response()->json($this->clusterDataService->getIncidentsFeed(), 200);
    }
}
