<?php

namespace App\Jobs;

use App\Models\ClusterData;
use App\Services\OpenTakCryptoService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use PhpMqtt\Client\Facades\MQTT;

/**
 * Tells the OpenTAKServer plugin that a Duck's SOS incident has been
 * resolved, mirroring ATAK's own <emergency cancel="true"/> convention so
 * the corresponding OTS Alert record's cancel_time gets set and the
 * emergency stops being shown as active. Dispatched from
 * DashboardController::updateIncidentStatus() the moment an incident's
 * status actually transitions to 'resolved' (not on every PATCH that
 * merely leaves it resolved, and not for resolveStraySiblings()'s
 * housekeeping resolutions of stray duplicate rows).
 */
class PublishOpenTakSosCancel implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(private readonly string $duckId) {}

    public function handle(OpenTakCryptoService $openTakCrypto): void
    {
        if (!config('services.opentak.enabled') || !$openTakCrypto->isConfigured()) {
            return;
        }

        // Last known position so the cancel event still carries a valid
        // <point> -- OTS's CoT ingest pipeline expects one on every event,
        // and re-using the duck's last fix means the marker doesn't jump.
        $record = ClusterData::where('duck_id', $this->duckId)
            ->orderByDesc('id')
            ->first();

        $lat = $record && $record->gps_lat !== null ? (float) $record->gps_lat : null;
        $lon = $record && $record->gps_lng !== null ? (float) $record->gps_lng : null;

        $plaintext = json_encode([
            'duck_id'    => $this->duckId,
            'lat'        => $lat,
            'lon'        => $lon,
            'sos_cancel' => true,
            'timestamp'  => now()->toIso8601String(),
        ]);

        $encrypted = $openTakCrypto->encryptEvent($plaintext);

        if ($encrypted === null) {
            Log::warning('PublishOpenTakSosCancel: encryption failed or bridge unconfigured, dropping', [
                'duck_id' => $this->duckId,
            ]);

            return;
        }

        $envelope = json_encode([
            'v'    => 1,
            'data' => $encrypted,
        ]);

        MQTT::publish(config('services.opentak.event_topic'), $envelope);

        Log::debug('PublishOpenTakSosCancel: published encrypted SOS cancel', [
            'duck_id' => $this->duckId,
            'topic'   => config('services.opentak.event_topic'),
        ]);
    }
}
