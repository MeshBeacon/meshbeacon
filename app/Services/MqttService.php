<?php

namespace App\Services;

use App\Models\DuckIdentity;
use PhpMqtt\Client\Facades\MQTT;

class MqttService
{
    public function __construct(private DuckCryptoService $duckCrypto) {}

    /**
     * Publish a command message to the hub via MQTT.
     *
     * $target is a raw 8-byte DUID (arbitrary binary -- see
     * App\Jobs\ProcessMqttMessage's duck_id storage), which json_encode()
     * cannot represent directly (fails outright on non-UTF-8 bytes).
     * Base64-encode it for safe JSON transport, same fix already applied
     * to the uplink DeviceID field in the gateway's Init.cpp --
     * PapaDuck.ino's handleIncomingMqttMessages() decodes it back before
     * use. "BROADCAST" is a literal sentinel (see PapaDuck.ino), left
     * as-is.
     */
    public function sendCommand(string $message, string $target, int $topic = 22): void
    {
        $payload = json_encode([
            'target'  => $target === 'BROADCAST' ? 'BROADCAST' : base64_encode($target),
            'topic'   => $topic,
            'message' => $message,
        ]);

        MQTT::publish('hub/command', $payload);
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
                $this->sendCommand($encrypted, $duckId, DuckCryptoService::TOPIC_ENCRYPTED_CMD);

                return;
            }
        }

        $this->sendCommand($plaintext, $duckId, 22);
    }
}
