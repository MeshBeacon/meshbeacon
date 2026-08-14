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

        // The gateway base64-encodes DeviceID before transport (raw,
        // hash-derived DUIDs are arbitrary binary and get truncated/
        // corrupted by NUL-terminated string handling otherwise -- see
        // Init.cpp). Decode back to the original raw bytes once, here, and
        // use that everywhere below (duck_id storage, AAD reconstruction)
        // so a corrupted/undecodable value can't silently propagate.
        $sduidRaw = $this->decodeDeviceId($data['payload']['DeviceID'] ?? null);

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
            $this->handleIdentityAnnounce($sduidRaw, $data['payload']['Message'] ?? null, $data);

            return;
        }

        // Encrypted topics (sealed_uplink, encrypted_data) arrive as base64
        // ciphertext with the real app-level topic hidden on purpose (see
        // docs/crypto-design.tex, "Payload Encoding Order"). Recover the
        // real topic + plaintext here, BEFORE any topic-based branching
        // below (e.g. the SOS-ack/Telegram-alert check), so an encrypted
        // SOS is treated identically to a plaintext one.
        $topicName = $eventType;
        $message = $data['payload']['Message'] ?? null;

        if ($eventType === 'sealed_uplink' || $eventType === 'encrypted_data') {
            [$topicName, $message] = $this->decryptUplink($duckCrypto, $duckPayloadDecoder, $eventType, $data, $message, $sduidRaw);
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
        $isSosAlert  = $record->topic === 'alert';
        $isSosStatus = $record->topic === 'status'
            && str_contains($record->payload ?? '', 'SOS');

        if (($isSosAlert || $isSosStatus) && $record->duck_id) {
            // SendSosAck retries itself (with backoff) over the lossy LoRa
            // link and gives up cleanly if all attempts fail, so a single
            // dispatch here is enough.
            SendSosAck::dispatch($record->duck_id);
            Log::info("ProcessMqttMessage: SOS ack queued for {$record->duck_id}");

            SendTelegramAlert::dispatch($record->duck_id, $record->display_text ?? '', $record->map_url);
            Log::info("ProcessMqttMessage: Telegram alert queued for {$record->duck_id}");
        }
    }

    /**
     * Decrypt a sealed_uplink/encrypted_data payload and recover the
     * original app-level topic + plaintext. Returns [topicName, message]
     * -- falls back to ['unknown', $messageB64] (leaving the ciphertext
     * untouched, never guessed-at) on any failure: crypto not configured,
     * missing DeviceID, or auth failure.
     *
     * @return array{0: string, 1: ?string}
     */
    private function decryptUplink(DuckCryptoService $duckCrypto, DuckPayloadDecoder $duckPayloadDecoder, string $eventType, array $data, ?string $messageB64, ?string $sduid): array
    {
        $sduid = (string) $sduid;

        if (!$duckCrypto->isConfigured() || $messageB64 === null || $sduid === '') {
            Log::warning('ProcessMqttMessage: cannot decrypt uplink (unconfigured or missing DeviceID)', [
                'event_type' => $eventType,
                'message_id' => $data['MessageID'] ?? null,
            ]);

            return ['unknown', $messageB64];
        }

        if ($eventType === 'encrypted_data') {
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
            $aad = $duckCrypto->buildHeaderAad($sduid, DuckCryptoService::PAPADUCK_DUID, DuckCryptoService::TOPIC_ENCRYPTED_DATA);
            $plaintext = $duckCrypto->decryptFromDuck($identity->public_key, $messageB64, $aad);
        } else {
            $aad = $duckCrypto->buildHeaderAad($sduid, DuckCryptoService::PAPADUCK_DUID, DuckCryptoService::TOPIC_SEALED_UPLINK);
            $plaintext = $duckCrypto->unsealFromDuck($messageB64, $aad);
        }

        if ($plaintext === null || $plaintext === '') {
            Log::warning('ProcessMqttMessage: uplink decrypt failed (auth failure or malformed payload)', [
                'event_type' => $eventType,
                'message_id' => $data['MessageID'] ?? null,
            ]);

            return ['unknown', $messageB64];
        }

        return $this->splitDecryptedTopic($duckPayloadDecoder, $plaintext, $data);
    }

    /**
     * Split decrypted plaintext into [topicName, body]: the first byte is
     * the original app-level topic (see Duck::sendSealedData()/
     * sendEncryptedData()), the rest is the payload. Protobuf-marked
     * bodies (gps/alert/health/status) are decoded via DuckPayloadDecoder
     * into the same legacy-text format the gateway itself produces for
     * unencrypted traffic; any other/unparseable protobuf body is stored
     * base64-encoded rather than mis-parsed as text.
     *
     * @return array{0: string, 1: string}
     */
    private function splitDecryptedTopic(DuckPayloadDecoder $duckPayloadDecoder, string $plaintext, array $data): array
    {
        $topicCode = ord($plaintext[0]);
        $body = substr($plaintext, 1);
        $topicName = self::TOPIC_NAMES[$topicCode] ?? 'unknown';

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
    private function handleIdentityAnnounce(?string $sduidRaw, ?string $pubkeyB64, array $data): void
    {
        if ($sduidRaw === null || $sduidRaw === '' || $pubkeyB64 === null || $pubkeyB64 === '') {
            Log::warning('ProcessMqttMessage: identity_announce missing DeviceID or public key', [
                'message_id' => $data['MessageID'] ?? null,
            ]);

            return;
        }

        $pubkeyRaw = base64_decode($pubkeyB64, true);

        if ($pubkeyRaw === false || strlen($pubkeyRaw) !== SODIUM_CRYPTO_BOX_PUBLICKEYBYTES) {
            Log::warning('ProcessMqttMessage: identity_announce public key is not valid base64 or wrong length', [
                'message_id' => $data['MessageID'] ?? null,
            ]);

            return;
        }

        $identity = DuckIdentity::firstOrCreate(
            ['duck_id' => $sduidRaw],
            ['public_key' => $pubkeyB64, 'first_seen_at' => now()]
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

    /**
     * Decode the wire-format DeviceID (base64, since raw hash-derived DUIDs
     * are arbitrary binary -- see Init.cpp) back to the original raw bytes.
     * Returns null if the field is missing/empty or not valid base64, so a
     * corrupted/malformed value can never silently propagate into AAD
     * reconstruction or duck_id storage.
     */
    private function decodeDeviceId(?string $encoded): ?string
    {
        if ($encoded === null || $encoded === '') {
            return null;
        }

        $decoded = base64_decode($encoded, true);

        if ($decoded === false) {
            Log::warning('ProcessMqttMessage: DeviceID is not valid base64', [
                'device_id_raw' => $encoded,
            ]);

            return null;
        }

        return $decoded;
    }
}
