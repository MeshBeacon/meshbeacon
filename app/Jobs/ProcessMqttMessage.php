<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\ClusterData;
use App\Models\DuckIdentity;
use App\Jobs\SendSosAck;
use App\Jobs\SendTelegramAlert;
use App\Jobs\SyncRecordToCloud;
use App\Services\DuckCryptoService;
use App\Services\DuckPayloadDecoder;
use App\Services\MqttService;
use Illuminate\Support\Facades\Log;

class ProcessMqttMessage implements ShouldQueue
{
    use Queueable;

    /**
     * Maps CdpPacket.h's `topics`/`reservedTopic` byte values (the first
     * plaintext byte recovered after decrypting a sealed_uplink/
     * encrypted_data payload -- see Duck::sendSealedData()/
     * sendEncryptedData()) back to the same topic name strings used by
     * CdpPacket::topicToString() on the firmware/gateway side.
     */
    private const TOPIC_NAMES = [
        0x10 => 'status',
        0x11 => 'cpm',
        0x13 => 'sensor',
        0x14 => 'alert',
        0x15 => 'health',
        0x16 => 'dcmd',
        0xEA => 'gps',
        0xEF => 'mq7',
        0xFA => 'gp2y',
        0xFB => 'bmp280',
        0xFC => 'dht11',
        0xFD => 'pir',
        0xFE => 'bmp180',
    ];

    /**
     * Format::kProtobuf marker byte (src/payloads/DuckPayloads.h). PHP-side
     * protobuf decoding of duck_payloads.proto is not implemented yet, so
     * decrypted payloads carrying this marker are stored base64-encoded
     * rather than mis-parsed as text.
     */
    private const PROTOBUF_MARKER = 0x01;

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
    public function handle(DuckCryptoService $duckCrypto, DuckPayloadDecoder $duckPayloadDecoder): void
    {
	    $data = json_decode($this->payload, true);

        // DeviceID is the operator-assigned, human-readable duck name (e.g.
        // via the DUCK_ID build flag), sent as plain text.
        $sduidRaw = $data['payload']['DeviceID'] ?? null;

        // Safety net: MqttSubscribe already filters "unknown" eventType
        // messages before dispatch, but this guards against jobs that were
        // already queued before that filter was deployed (or any future
        // caller that dispatches this job directly).
        if (strtolower((string) ($data['eventType'] ?? '')) === 'unknown') {
            Log::debug('ProcessMqttMessage: skipping unknown eventType', [
                'message_id' => $data['MessageID'] ?? null,
            ]);

            return;
        }

	    Log::info("Processing ClusterDuck Data...");

        $eventType = strtolower((string) ($data['eventType'] ?? ''));

        // identity_announce (CdpPacket.h reservedTopic::identity_announce,
        // sent via Duck::announceIdentity()) is a protocol/key-management
        // message, not sensor/alert data -- TOFU-populate duck_identities
        // and stop, rather than creating a nonsensical ClusterData row for
        // it or running it through the SOS-ack check below.
        if ($eventType === 'identity_announce') {
            $this->handleIdentityAnnounce($duckCrypto, $sduidRaw, $data['payload']['Message'] ?? null, $data);

            return;
        }

        // Encrypted topics (sealed_uplink, encrypted_data) arrive with the
        // real app-level topic already recovered by the gateway into
        // eventType (it's a cleartext, AAD-authenticated prefix byte on the
        // wire -- see Duck::sendSealedData()/sendEncryptedData() in
        // meshbeacon-firmware -- never itself encrypted), and an explicit
        // payload.transport field marking which encrypted path it came in
        // on. Recover the plaintext here, BEFORE any topic-based branching
        // below (e.g. the SOS-ack/Telegram-alert check), so an encrypted
        // SOS is treated identically to a plaintext one.
        $topicName = $eventType;
        $message = $data['payload']['Message'] ?? null;
        $transport = strtolower((string) ($data['payload']['transport'] ?? ''));

        if ($transport === 'sealed_uplink' || $transport === 'encrypted_data') {
            [$topicName, $message] = $this->decryptUplink($duckCrypto, $duckPayloadDecoder, $transport, $eventType, $data, $message, $sduidRaw);
        }

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
	      'duck_id'     => $sduidRaw,
              'topic'       => $topicName,
              'message_id'  => $data["MessageID"],
              'payload'     => $message,
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
        // Dispatched immediately/automatically here (not just from the
        // dashboard's manual sos-ack/bulk-acknowledge endpoints, see
        // DashboardController) so the device gets its "SOS DITERIMA" relief
        // cue right away rather than waiting on a human operator to click
        // Acknowledge. SendSosAck itself resends 3x at 10 s intervals
        // regardless (LoRa has no delivery confirmation) -- an operator
        // manually acknowledging afterwards simply queues another round,
        // which is harmless.
        $isSosAlert  = $record->topic === 'alert';
        $isSosStatus = $record->topic === 'status'
            && str_contains($record->payload ?? '', 'SOS');

        if (($isSosAlert || $isSosStatus) && $record->duck_id) {
            SendSosAck::dispatch($record->duck_id, 1);
            Log::info("ProcessMqttMessage: SOS ack queued for {$record->duck_id}");

            SendTelegramAlert::dispatch($record->duck_id, $record->display_text ?? '', $record->map_url);
            Log::info("ProcessMqttMessage: Telegram alert queued for {$record->duck_id}");
        }

        // Evaluate automated rules for this telemetry ping
        EvaluateRules::dispatch($record);
    }

    /**
     * Decrypt a sealed_uplink/encrypted_data payload and recover the
     * plaintext. Returns [topicName, message] -- falls back to
     * ['unknown', $messageB64] (leaving the ciphertext untouched, never
     * guessed-at) on any failure: crypto not configured, missing
     * DeviceID, unmappable topic, or auth failure.
     *
     * @return array{0: string, 1: ?string}
     */
    private function decryptUplink(DuckCryptoService $duckCrypto, DuckPayloadDecoder $duckPayloadDecoder, string $transport, string $topicName, array $data, ?string $messageB64, ?string $sduid): array
    {
        $sduid = (string) $sduid;

        if (!$duckCrypto->isConfigured() || $messageB64 === null || $sduid === '') {
            Log::warning('ProcessMqttMessage: cannot decrypt uplink (unconfigured or missing DeviceID)', [
                'transport' => $transport,
                'message_id' => $data['MessageID'] ?? null,
            ]);

            return ['unknown', $messageB64];
        }

        // The gateway recovers the real app-level topic from the cleartext,
        // AAD-authenticated prefix byte Duck::sendSealedData()/
        // sendEncryptedData() sends (see meshbeacon-firmware's Duck.h) and
        // reports it as $topicName (eventType). Both firmware and this AAD
        // MUST use that same real topic byte -- NOT the generic
        // TOPIC_SEALED_UPLINK/TOPIC_ENCRYPTED_DATA constant -- or the AEAD
        // tag will never verify. Topics outside TOPIC_NAMES (e.g.
        // example-sketch-only MTALK/op-text channels) can't be mapped back
        // to their exact on-air byte from the name alone, so those cannot
        // be decrypted here -- pre-existing limitation, unrelated to this.
        $realTopicByte = array_search($topicName, self::TOPIC_NAMES, true);

        if ($realTopicByte === false) {
            Log::warning('ProcessMqttMessage: cannot map topic name back to on-air byte for AAD, dropping', [
                'topic' => $topicName,
                'message_id' => $data['MessageID'] ?? null,
            ]);

            return ['unknown', $messageB64];
        }

        if ($transport === 'encrypted_data') {
            // Session mode (static-static X25519 ECDH between two Ducks, or
            // a Duck targeting OpenDMS the same way). Needs the sender's
            // long-term public key, learned via identity_announce TOFU.
            $identity = DuckIdentity::query()->where('duck_id', $sduid)->first();

            if (!$identity) {
                Log::warning('ProcessMqttMessage: encrypted_data sender not in duck_identities (no identity_announce seen yet)', [
                    'message_id' => $data['MessageID'] ?? null,
                ]);

                return ['unknown', $messageB64];
            }

            // Best-effort: this only succeeds if the Duck actually targeted
            // OpenDMS as its session peer (PAPADUCK_DUID convention, same as
            // sealed_uplink). Genuine Duck<->Duck session traffic the
            // gateway merely relays past OpenDMS will correctly fail auth
            // here and fall through to 'unknown' -- OpenDMS was never meant
            // to be able to decrypt that traffic, so that outcome is
            // expected/safe, not a bug.
            $aad = $duckCrypto->buildHeaderAad($sduid, DuckCryptoService::PAPADUCK_DUID, $realTopicByte);
            $plaintext = $duckCrypto->decryptFromDuck($identity->public_key, $messageB64, $aad);
        } else {
            $aad = $duckCrypto->buildHeaderAad($sduid, DuckCryptoService::PAPADUCK_DUID, $realTopicByte);
            $plaintext = $duckCrypto->unsealFromDuck($messageB64, $aad);
        }

        if ($plaintext === null || $plaintext === '') {
            Log::warning('ProcessMqttMessage: uplink decrypt failed (auth failure or malformed payload)', [
                'transport' => $transport,
                'message_id' => $data['MessageID'] ?? null,
            ]);

            return ['unknown', $messageB64];
        }

        Log::info('ProcessMqttMessage: uplink decrypted successfully', [
            'transport' => $transport,
            'sduid' => $sduid,
            'message_id' => $data['MessageID'] ?? null,
        ]);

        // The decrypted plaintext is now the payload only -- the app-level
        // topic already arrived as a cleartext prefix byte (recovered by
        // the gateway into $topicName above), it is no longer folded into
        // the encrypted plaintext. See Duck::sendSealedData()/
        // sendEncryptedData() in meshbeacon-firmware.
        $body = $plaintext;

        if ($body !== '' && ord($body[0]) === self::PROTOBUF_MARKER) {
            $decoded = $duckPayloadDecoder->decode($topicName, $body);

            if ($decoded !== null) {
                $body = $decoded;
            } else {
                Log::warning('ProcessMqttMessage: decrypted payload is protobuf-encoded but could not be decoded, storing raw bytes', [
                    'topic' => $topicName,
                    'message_id' => $data['MessageID'] ?? null,
                ]);

                $body = base64_encode($body);
            }
        }

        return [$topicName, $body];
    }

    /**
     * Trust-on-first-use (TOFU) population of duck_identities from a
     * Duck's identity_announce broadcast (CdpPacket.h
     * reservedTopic::identity_announce -- raw 32-byte X25519 public key as
     * the entire data section, see Duck::announceIdentity()). First
     * announcement seen for a given duck_id wins; later announces for the
     * same duck_id are ignored so a spoofed-DUID replay can't swap out an
     * already-trusted peer's key. Populating this table is what lets
     * SendSosAck send an encrypted reply instead of a plaintext one.
     */
    private function handleIdentityAnnounce(DuckCryptoService $duckCrypto, ?string $sduidRaw, ?string $pubkeyB64, array $data): void
    {
        if ($sduidRaw === null || $sduidRaw === '' || $pubkeyB64 === null || $pubkeyB64 === '') {
            Log::warning('ProcessMqttMessage: identity_announce missing DeviceID or public key', [
                'message_id' => $data['MessageID'] ?? null,
            ]);

            return;
        }

        $payloadRaw = base64_decode($pubkeyB64, true);

        if ($payloadRaw === false) {
            Log::warning('ProcessMqttMessage: identity_announce payload is not valid base64', [
                'message_id' => $data['MessageID'] ?? null,
            ]);

            return;
        }

        // Verifies the payload is a well-formed 32-byte X25519 public key,
        // mirroring Duck::learnPeerIdentity()'s TOFU policy -- see
        // DuckCryptoService::verifyIdentityAnnounce().
        $pubkeyRaw = $duckCrypto->verifyIdentityAnnounce($payloadRaw);

        if ($pubkeyRaw === null) {
            Log::warning('ProcessMqttMessage: identity_announce rejected (wrong length)', [
                'message_id' => $data['MessageID'] ?? null,
            ]);

            return;
        }

        $identity = DuckIdentity::firstOrCreate(
            ['duck_id' => $sduidRaw],
            ['public_key' => base64_encode($pubkeyRaw), 'first_seen_at' => now()]
        );

        if ($identity->wasRecentlyCreated) {
            Log::info('ProcessMqttMessage: learned new duck identity via TOFU', [
                'message_id' => $data['MessageID'] ?? null,
            ]);
        } else {
            Log::debug('ProcessMqttMessage: identity_announce received for already-known duck_id, ignoring (TOFU)', [
                'message_id' => $data['MessageID'] ?? null,
            ]);
        }
    }

}
