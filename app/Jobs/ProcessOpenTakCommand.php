<?php

namespace App\Jobs;

use App\Services\MqttService;
use App\Services\OpenTakCryptoService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Decrypts and executes a mesh command received from the OpenTAKServer
 * plugin over the encrypted MQTT bridge (see docs/OPENTAK_BRIDGE.md).
 * Dispatched by MqttSubscribe's `opentak.command_topic` handler with the
 * raw (still-encrypted) MQTT payload string.
 *
 * Any failure here (bad JSON, auth failure, missing/misconfigured keys,
 * unknown duck_id) is logged and the message is dropped -- there is no
 * retry, since a stale command re-delivered later could be dangerous
 * (e.g. an outdated GPS poll request) and the operator-facing OTS plugin
 * is expected to surface delivery failures on its own side.
 */
class ProcessOpenTakCommand implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private readonly string $rawPayload) {}

    public function handle(OpenTakCryptoService $openTakCrypto, MqttService $mqttService): void
    {
        if (!config('services.opentak.enabled') || !$openTakCrypto->isConfigured()) {
            Log::debug('ProcessOpenTakCommand: bridge disabled or unconfigured, dropping command');
            return;
        }

        $envelope = json_decode($this->rawPayload, true);
        $ciphertext = $envelope['data'] ?? null;

        if (!is_string($ciphertext)) {
            Log::warning('ProcessOpenTakCommand: malformed envelope, dropping command');
            return;
        }

        $plaintext = $openTakCrypto->decryptCommand($ciphertext);

        if ($plaintext === null) {
            Log::warning('ProcessOpenTakCommand: decryption failed (bad auth, malformed payload, or misconfigured keys), dropping command');
            return;
        }

        $data = json_decode($plaintext, true);
        $duckId = $data['duck_id'] ?? null;
        $message = $data['message'] ?? null;

        if (!is_string($duckId) || $duckId === '' || !is_string($message) || $message === '') {
            Log::warning('ProcessOpenTakCommand: decrypted command missing duck_id/message, dropping');
            return;
        }

        $delivered = $mqttService->sendEncryptedCommand($message, $duckId);

        Log::info('ProcessOpenTakCommand: command relayed to mesh', [
            'duck_id'             => $duckId,
            'encrypted_to_duck'   => $delivered,
        ]);
    }
}
