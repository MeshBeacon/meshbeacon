<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClusterData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Receives records pushed by a field node's SyncRecordToCloud outbox job
 * (see docs/HYBRID_DEPLOYMENT.md). Only meaningful on an instance running
 * in "central" mode — it just needs CENTRAL_DMS_TOKEN set so field nodes
 * can authenticate (see VerifyCentralDmsToken middleware).
 */
class IngestController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'duck_id'     => ['required', 'string', 'max:255'],
            'topic'       => ['required', 'string', 'max:255'],
            'message_id'  => ['required', 'string', 'max:255'],
            'payload'     => ['nullable', 'string'],
            'path'        => ['nullable', 'string'],
            'origin'      => ['nullable', 'string', 'max:32'],
            'destination' => ['nullable', 'string', 'max:32'],
            'hops'        => ['nullable', 'integer'],
            'duck_type'   => ['nullable', 'integer'],
            'created_at'  => ['nullable', 'date'],
        ]);

        // Dedupe on (duck_id, message_id): a field node may legitimately
        // retry this POST if it never saw our response (e.g. the network
        // dropped after we'd already committed), so ingestion must be
        // idempotent rather than always inserting a new row.
        $record = ClusterData::updateOrCreate(
            [
                'duck_id'    => $data['duck_id'],
                'message_id' => $data['message_id'],
            ],
            [
                'topic'       => $data['topic'],
                'payload'     => $data['payload'] ?? null,
                'path'        => $data['path'] ?? null,
                'origin'      => $data['origin'] ?? null,
                'destination' => $data['destination'] ?? null,
                'hops'        => $data['hops'] ?? null,
                'duck_type'   => $data['duck_type'] ?? null,
                // This server IS the central destination for this record —
                // no further sync is applicable (see docs/HYBRID_DEPLOYMENT.md).
                'synced'      => null,
                'synced_at'   => null,
            ]
        );

        // created_at isn't mass-assignable (deliberately excluded from
        // $fillable); set it explicitly so we preserve the field node's
        // original event time instead of defaulting to "now" on ingest.
        if (!empty($data['created_at'])) {
            $record->created_at = $data['created_at'];
            $record->save();
        }

        return response()->json(['message' => 'Record ingested', 'id' => $record->id], 200);
    }
}
