<?php

namespace App\Services;

use App\Models\DuckIdentity;
use Illuminate\Support\Facades\Log;
use PhpMqtt\Client\Facades\MQTT;

class MqttService
{
    public function __construct(private DuckCryptoService $duckCrypto) {}

    /**
     * Publish a command message to the hub via MQTT.
     *
     * $target is the operator-assigned, human-readable duck name (DUID),
     * sent as plain text for every topic, including encrypted_cmd --
     * clusterduckd.c's mqtt_message_arrived() treats it as literal text,
     * padded/truncated to 8 bytes. "BROADCAST" is a literal sentinel, left
     * as-is.
     */
    public function sendCommand(string $message, string $target, int $topic = 22, ?string $encoding = null): void
    {
        $data = [
            'target'  => $target,
            'topic'   => $topic,
            'message' => $message,
        ];

        // Tell clusterduckd.c's mqtt_message_arrived() to base64-decode
        // $message before sending it on-air -- required whenever $message
        // is already base64 text (e.g. DuckCryptoService::encryptToDuck()'s
        // output), otherwise the gateway forwards the literal base64
        // characters as the "ciphertext" instead of the real binary
        // nonce||ciphertext||tag, and the Duck can never decrypt it.
        if ($encoding !== null) {
            $data['encoding'] = $encoding;
        }

        MQTT::publish('hub/command', json_encode($data));
    }

    /**
     * Send a downlink command encrypted to a specific Duck, using
     * reservedTopic::encrypted_cmd (0x08) instead of the plaintext dcmd
     * (0x16) topic -- see docs/crypto-design.tex (meshbeacon-firmware
     * repo), "OpenDMS -> Duck (operator-initiated downlink)".
     *
     * Requires the target Duck's public key to already be on file (TOFU
     * via identity_announce, see App\Models\DuckIdentity) and OpenDMS's
     * static keypair to be configured (services.duck_crypto). If either
     * is missing, or encryption otherwise fails, falls back to a plaintext
     * dcmd send -- same best-effort pattern SendSosAck used previously.
     */
    public function sendEncryptedCommand(string $plaintext, string $duckId): void
    {
        $identity = DuckIdentity::query()->where('duck_id', $duckId)->first();

        if ($identity && $this->duckCrypto->isConfigured()) {
            $aad = $this->duckCrypto->buildHeaderAad(
                DuckCryptoService::PAPADUCK_DUID,
                $duckId,
                DuckCryptoService::TOPIC_ENCRYPTED_CMD
            );
            $encrypted = $this->duckCrypto->encryptToDuck($identity->public_key, $plaintext, $aad);

            if ($encrypted !== null) {
                Log::info("MqttService: sendEncryptedCommand encrypted successfully for {$duckId}");
                $this->sendCommand($encrypted, $duckId, DuckCryptoService::TOPIC_ENCRYPTED_CMD, 'base64');

                return;
            }

            Log::warning("MqttService: sendEncryptedCommand encryption failed for {$duckId}, falling back to plaintext dcmd");
        } elseif (!$identity) {
            Log::info("MqttService: sendEncryptedCommand no known identity for {$duckId} yet (no identity_announce seen), sending plaintext dcmd");
        } elseif (!$this->duckCrypto->isConfigured()) {
            Log::info("MqttService: sendEncryptedCommand OpenDMS duck_crypto keypair not configured, sending plaintext dcmd");
        }

        $this->sendCommand($plaintext, $duckId, 22);
    }
}
