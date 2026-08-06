<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authenticates inbound /api/ingest requests from field nodes.
 *
 * This is checked on the CENTRAL server only. The central instance's
 * `CENTRAL_DMS_TOKEN` env var holds the same pre-shared secret that field
 * nodes send as their Bearer token (their own `CENTRAL_DMS_URL` points at
 * this server, and their `CENTRAL_DMS_TOKEN` must match this value).
 */
class VerifyCentralDmsToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = config('services.central_dms.token');

        if (empty($expected)) {
            return response()->json([
                'message' => 'Ingestion is not configured on this server.',
            ], 503);
        }

        $provided = (string) $request->bearerToken();

        if ($provided === '' || !hash_equals($expected, $provided)) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        return $next($request);
    }
}
