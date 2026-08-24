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
 * Forwards a ClusterData telemetry record to the OpenTAKServer plugin, as
 * an encrypted JSON envelope published on the `opentak.event_topic` MQTT
 * topic (see docs/OPENTAK_BRIDGE.md). Only dispatched by ProcessMqttMessage
 * when the bridge is enabled and configured; this job re-checks both in
 * case configuration changed between dispatch and execution (e.g. queue
 * backlog during a config change).
 */
class PublishOpenTakEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(private readonly int $recordId) {}

    public function handle(OpenTakCryptoService $openTakCrypto): void
    {
        if (!config('services.opentak.enabled') || !$openTakCrypto->isConfigured()) {
            return;
        }

        $record = ClusterData::find($this->recordId);

        if (!$record) {
            Log::warning("PublishOpenTakEvent: record {$this->recordId} not found, skipping.");
            return;
        }

        $isSosAlert = $record->topic === 'alert';
        $isSosStatus = $record->topic === 'status'
            && str_contains($record->payload ?? '', 'SOS');

        // A genuine field-device message -- either free text typed on the
        // companion phone app, or a canned "Roger" from triple-clicking the
        // physical button (see DuckPayloadDecoder::statusMsgToLegacyText(),
        // legacy text "MSG,SRC:DEVICE,TEXT:..." / "MSG,URGENCY:...,TEXT:...").
        // Distinct from an embedded status-topic SOS ("SOS,..." above) and
        // from routine gps/sensor telemetry, so only these become an actual
        // ATAK GeoChat entry on the OTS side rather than spamming the chat
        // log with every telemetry ping.
        $isChatMessage = $record->topic === 'status'
            && str_starts_with($record->payload ?? '', 'MSG,');

        $lat = $record->gps_lat !== null ? (float) $record->gps_lat : null;
        $lon = $record->gps_lng !== null ? (float) $record->gps_lng : null;

        $plaintext = json_encode([
            'duck_id'    => $record->duck_id,
            'topic'      => $record->topic,
            'message_id' => $record->message_id,
            'message'    => $record->display_text,
            'lat'        => $lat,
            'lon'        => $lon,
            // GPS/telemetry extras -- null when the payload doesn't carry
            // that field (see ClusterData's getGps*Attribute accessors).
            'altitude'   => $record->gps_alt,   // metres (ALT:<n>)
            'speed'      => $record->gps_spd,   // km/h (SPD:<n>)
            'heading'    => $record->gps_hdg,   // degrees (HDG:<n>)
            'battery'    => $record->gps_batt,  // percent (BATT:<n>)
            'rssi'       => $record->gps_rssi,  // dBm (RSSI:<n>)
            'snr'        => $record->gps_snr,   // dB (SNR:<n>)
            'path'       => $record->path,
            'duck_type'  => $record->duck_type,
            'sos'        => $isSosAlert || $isSosStatus,
            'chat'       => $isChatMessage,
            'timestamp'  => $record->created_at?->toIso8601String() ?? now()->toIso8601String(),
        ]);

        $encrypted = $openTakCrypto->encryptEvent($plaintext);

        if ($encrypted === null) {
            Log::warning('PublishOpenTakEvent: encryption failed or bridge unconfigured, dropping event', [
                'record_id' => $this->recordId,
            ]);

            return;
        }

        $envelope = json_encode([
            'v'    => 1,
            'data' => $encrypted,
        ]);

        MQTT::publish(config('services.opentak.event_topic'), $envelope);

        Log::debug('PublishOpenTakEvent: published encrypted event', [
            'record_id' => $this->recordId,
            'topic'     => config('services.opentak.event_topic'),
        ]);
    }
}
