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
     * topics::encrypted_cmd (0x1B) instead of the plaintext dcmd
     * (0x16) topic -- see docs/crypto-design.tex (meshbeacon-firmware
     * repo), "OpenDMS -> Duck (operator-initiated downlink)".
     *
     * Requires the target Duck's public key to already be on file (TOFU
     * via identity_announce, see App\Models\DuckIdentity) and OpenDMS's
     * static keypair to be configured (services.duck_crypto). If either
     * is missing, or encryption otherwise fails, falls back to a plaintext
     * dcmd send -- same best-effort pattern SendSosAck used previously.
     *
     * Returns true if the command was sent via the authenticated
     * reservedTopic::encrypted_cmd channel, false if it was sent as a
     * plaintext dcmd fallback. The firmware (see meshbeacon-firmware's
     * MamaDuck.h / example sketches) only treats a small set of privileged
     * directives (e.g. "SOS DITERIMA", "CMD:GPS_REQUEST") as authoritative
     * when they arrive via encrypted_cmd -- a plaintext dcmd fallback is
     * displayed as an ordinary message and does NOT trigger those
     * privileged actions. Callers sending a privileged directive should
     * check this return value and treat `false` as "not delivered" rather
     * than assuming the plaintext fallback still works.
     */
    public function sendEncryptedCommand(string $plaintext, string $duckId): bool
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

                return true;
            }

            Log::warning("MqttService: sendEncryptedCommand encryption failed for {$duckId}, falling back to plaintext dcmd");
        } elseif (!$identity) {
            Log::info("MqttService: sendEncryptedCommand no known identity for {$duckId} yet (no identity_announce seen), sending plaintext dcmd");
        } elseif (!$this->duckCrypto->isConfigured()) {
            Log::info("MqttService: sendEncryptedCommand OpenDMS duck_crypto keypair not configured, sending plaintext dcmd");
        }

        $this->sendCommand($plaintext, $duckId, 22);

        return false;
    }

    /**
     * Send an Emergency Broadcast, authenticated (not encrypted) with the
     * deployment's mesh group key when configured (DuckCryptoService::
     * authenticateGroupBroadcast()). The message text is deliberately
     * sent as cleartext -- a life-safety alert should be readable by
     * anyone in range, including devices without the group key -- only
     * forgery is prevented. Unlike sendEncryptedCommand()'s encrypted_cmd
     * (point-to-point, a different shared secret per Duck), the mesh
     * group key is a single pre-shared secret every Duck in the
     * deployment can hold, making it the only channel that fits a
     * "verifiable by every Duck" broadcast.
     *
     * Falls back to a plain unauthenticated topic-24 send if the group
     * key isn't configured -- same best-effort pattern as
     * sendEncryptedCommand().
     *
     * Returns true if the broadcast was sent authenticated, false if it
     * was sent as an unauthenticated fallback. meshbeacon-firmware's
     * MamaDuck.ino rejects the unauthenticated fallback outright once a
     * Duck has its own mesh group key configured, so callers should treat
     * `false` as "delivered only to un-provisioned Ducks", not "delivered
     * to everyone".
     */
    public function sendGroupBroadcast(string $message, string $target = 'BROADCAST'): bool
    {
        $authenticated = $this->duckCrypto->authenticateGroupBroadcast($message);

        if ($authenticated !== null) {
            Log::info('MqttService: sendGroupBroadcast authenticated successfully');
            $this->sendCommand($authenticated, $target, DuckCryptoService::TOPIC_BROADCAST, 'base64');

            return true;
        }

        Log::info('MqttService: sendGroupBroadcast mesh group key not configured, sending unauthenticated broadcast');
        $this->sendCommand($message, $target, DuckCryptoService::TOPIC_BROADCAST);

        return false;
    }
}
