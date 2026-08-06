<?php

namespace App\Http\Controllers;

use App\Services\ClusterDataService;
use Illuminate\Http\JsonResponse;

class MessageLogController extends Controller
{
    public function __construct(private ClusterDataService $clusterDataService)
    {}

    public function index()
    {
        return view('messages');
    }

    public function json(): JsonResponse
    {
        return response()->json($this->clusterDataService->getJsonFeed(), 200);
    }
}
