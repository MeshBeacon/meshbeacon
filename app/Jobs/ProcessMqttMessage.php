<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\ClusterData;
use App\Jobs\SendSosAck;
use App\Jobs\SyncRecordToCloud;
use App\Services\MqttService;
use Illuminate\Support\Facades\Log;

class ProcessMqttMessage implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(protected $payload)
    {
        $this->payload = $payload;
    }

    /**
     * Execute the job.
     *
     * Deployment-aware write strategy:
     *   - Production / standalone offline: `synced` left as null (not applicable).
     *     SyncRecordToCloud is never dispatched.
     *   - Hybrid (CENTRAL_DMS_URL configured): `synced` set to false (pending).
     *     SyncRecordToCloud is dispatched and will retry until delivery is confirmed.
     */
    public function handle(): void
    {
	    Log::info("Processing ClusterDuck Data...");
	    $data = json_decode($this->payload, true);

        // Robust path extraction.
        // Guards against: missing key, explicit null, empty array,
        // double-encoded JSON string (some concentrator versions), plain string.
        $rawPath = $data["payload"]["path"] ?? null;
        if (is_array($rawPath) && count($rawPath) > 0) {
            $path = implode(",", array_filter(array_map('strval', $rawPath)));
        } elseif (is_string($rawPath) && $rawPath !== '') {
            // Concentrator may double-encode the array as a JSON string.
            $decoded = json_decode($rawPath, true);
            $path = is_array($decoded)
                ? implode(",", array_filter(array_map('strval', $decoded)))
                : $rawPath;
        } else {
            $path = null;
        }

        if ($path === null) {
            Log::warning('ProcessMqttMessage: path is null', [
                'message_id'   => $data['MessageID'] ?? 'unknown',
                'payload_keys' => array_keys($data['payload'] ?? []),
                'raw_path'     => $rawPath,
            ]);
        }

        $isHybrid = !empty(config('services.central_dms.url'));

	    $record = ClusterData::create([
	      'duck_id'     => $data["payload"]["DeviceID"],
              'topic'       => $data["eventType"],
              'message_id'  => $data["MessageID"],
              'payload'     => $data["payload"]["Message"] ?? null,
	      'path'        => $path,
              'origin'      => $data["payload"]["origin"] ?? null,
              'destination' => $data["payload"]["destination"] ?? null,
              'hops'        => $data["payload"]["hops"],
              'duck_type'   => $data["payload"]["duckType"],
              // null = not applicable; false = pending sync (hybrid mode only)
              'synced'      => $isHybrid ? false : null,
	    ]);

        // Only enqueue the outbox sync job in hybrid mode.
        if ($isHybrid) {
            SyncRecordToCloud::dispatch($record->id)
                ->onQueue('sync')
                ->delay(now()->addSeconds(5));

            Log::info("ProcessMqttMessage: queued sync for record {$record->id}");
        }

        // Send SOS acknowledgment back to the originating duck so the device
        // can confirm the operator has received the distress signal.
        $isSosAlert  = $record->topic === 'alert';
        $isSosStatus = $record->topic === 'status'
            && str_contains($record->payload ?? '', 'SOS');

        if (($isSosAlert || $isSosStatus) && $record->duck_id) {
            // Dispatch 3 attempts with increasing delays so the device gets
            // multiple chances to receive the ACK over LoRa (lossy at distance).
            foreach ([0, 10, 20] as $i => $delaySec) {
                SendSosAck::dispatch($record->duck_id, $i + 1)
                    ->delay(now()->addSeconds($delaySec));
            }
            Log::info("ProcessMqttMessage: SOS ack queued (3 attempts) for {$record->duck_id}");
        }
    }
}
