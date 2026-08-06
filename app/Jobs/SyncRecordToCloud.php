<?php

namespace App\Jobs;

use App\Models\ClusterData;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Store-and-forward outbox job for hybrid field/cloud deployment.
 *
 * This job is ONLY dispatched when CENTRAL_DMS_URL is configured in the
 * environment, i.e. in hybrid deployment mode. In production mode (where
 * this application IS the central server) and in fully offline standalone
 * mode, this job is never dispatched and these columns remain null.
 *
 * Retry behaviour:
 *   - Up to 999 attempts so records accumulated during multi-day outages
 *     are all eventually delivered without operator intervention.
 *   - Exponential backoff: 30s → 60s → 2min → 5min → 10min.
 *   - On success the record's `synced` flag is set to true and `synced_at`
 *     is recorded for audit purposes.
 */
class SyncRecordToCloud implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 999;

    public array $backoff = [30, 60, 120, 300, 600];

    public function __construct(private readonly int $recordId) {}

    public function handle(): void
    {
        $centralUrl   = config('services.central_dms.url');
        $centralToken = config('services.central_dms.token');

        // Guard: if no central URL is set this job should never have been
        // dispatched, but handle it gracefully just in case.
        if (empty($centralUrl)) {
            Log::warning('SyncRecordToCloud: CENTRAL_DMS_URL is not set. Discarding job.');
            return;
        }

        $record = ClusterData::find($this->recordId);

        if (!$record) {
            Log::warning("SyncRecordToCloud: record {$this->recordId} not found, skipping.");
            return;
        }

        // Already synced by a previous attempt that didn't ACK properly.
        if ($record->synced === true) {
            return;
        }

        $response = Http::withToken($centralToken)
            ->timeout(10)
            ->post($centralUrl . '/api/ingest', [
                'duck_id'     => $record->duck_id,
                'topic'       => $record->topic,
                'message_id'  => $record->message_id,
                'payload'     => $record->payload,
                'path'        => $record->path,
                'origin'      => $record->origin,
                'destination' => $record->destination,
                'hops'        => $record->hops,
                'duck_type'   => $record->duck_type,
                'created_at'  => $record->created_at,
            ]);

        if ($response->successful()) {
            $record->update([
                'synced'    => true,
                'synced_at' => now(),
            ]);
            Log::info("SyncRecordToCloud: record {$this->recordId} synced successfully.");
        } else {
            // Throw so Laravel applies exponential backoff and retries.
            throw new \RuntimeException(
                "Central DMS returned HTTP {$response->status()} for record {$this->recordId}. Will retry."
            );
        }
    }
}
