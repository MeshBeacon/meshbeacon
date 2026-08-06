<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks incident-dispatch write actions (acknowledge, assign, notes,
 * status changes, bulk-acknowledge) when DASHBOARD_READONLY is enabled.
 *
 * This is the actual security boundary for a hybrid-deployment central
 * aggregator: dispatch is only ever meant to happen at the field site,
 * and central is monitoring-only (see docs/HYBRID_DEPLOYMENT.md). Hiding
 * the buttons in the UI is just UX politeness — this middleware is what
 * makes it structurally impossible to dispatch from a read-only instance.
 */
class PreventDashboardWritesWhenReadonly
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('services.central_dms.dashboard_readonly')) {
            return response()->json([
                'message' => 'This server is read-only. Incident dispatch actions are not available here.',
            ], 403);
        }

        return $next($request);
    }
}
