<?php

namespace App\Http\Controllers;

use App\Services\OperationalStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class HealthController extends Controller
{
    public function live(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'service' => config('app.name'),
            'generated_at' => now()->toIso8601String(),
        ]);
    }

    public function ready(OperationalStatus $status): JsonResponse
    {
        $payload = $status->snapshot();

        return response()->json($payload, $payload['ready'] ? 200 : 503);
    }

    public function operations(): View
    {
        return view('operations');
    }

    public function operationsStatus(OperationalStatus $status): JsonResponse
    {
        return response()->json($status->snapshot(private: true));
    }

    public function metrics(Request $request, OperationalStatus $status): Response|JsonResponse
    {
        $expected = (string) config('observability.metrics_token', '');
        $provided = (string) $request->bearerToken();

        if ($expected !== '' && ($provided === '' || ! hash_equals($expected, $provided))) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        return response($status->prometheus($status->snapshot()))
            ->header('Content-Type', 'text/plain; version=0.0.4; charset=utf-8');
    }
}
