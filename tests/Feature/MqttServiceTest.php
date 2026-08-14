<?php

namespace Tests\Feature;

use App\Models\DuckIdentity;
use App\Services\DuckCryptoService;
use App\Services\MqttService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpMqtt\Client\Facades\MQTT;
use Tests\TestCase;

/**
 * Verifies MqttService's downlink command transport, including the
 * base64 target-DUID encoding fix and the new sendEncryptedCommand()
 * switch from plaintext dcmd (0x16) to reservedTopic::encrypted_cmd
 * (0x08) -- see docs/crypto-design.tex (meshbeacon-firmware repo),
 * "OpenDMS -> Duck (operator-initiated downlink)".
 */
class MqttServiceTest extends TestCase
{
    use RefreshDatabase;

    private function rawX25519KeyPair(): array
    {
        $keyPair = sodium_crypto_box_keypair();

        return [
            'public' => sodium_crypto_box_publickey($keyPair),
            'private' => sodium_crypto_box_secretkey($keyPair),
        ];
    }

    public function test_send_command_base64_encodes_a_raw_binary_target(): void
    {
        $rawDuid = "\xAA\xBB\x00\xCC\xDD\xEE\xFF\x11"; // embedded 0x00, not valid UTF-8

        MQTT::shouldReceive('publish')
            ->once()
            ->with('hub/command', \Mockery::on(function (string $payload) use ($rawDuid) {
                $decoded = json_decode($payload, true);

                return $decoded !== null
                    && $decoded['target'] === base64_encode($rawDuid)
                    && $decoded['topic'] === 22
                    && $decoded['message'] === 'hello';
            }));

        app(MqttService::class)->sendCommand('hello', $rawDuid, 22);
    }

    public function test_send_command_leaves_the_broadcast_sentinel_untouched(): void
    {
        MQTT::shouldReceive('publish')
            ->once()
            ->with('hub/command', \Mockery::on(function (string $payload) {
                $decoded = json_decode($payload, true);

                return $decoded !== null && $decoded['target'] === 'BROADCAST';
            }));

        app(MqttService::class)->sendCommand('hello', 'BROADCAST', 22);
    }

    public function test_send_encrypted_command_uses_encrypted_cmd_topic_when_identity_known(): void
    {
        $opendms = $this->rawX25519KeyPair();
        $duck = $this->rawX25519KeyPair();
        $duckId = "\xAA\xBB\x00\xCC\xDD\xEE\xFF\x11";

        config([
            'services.duck_crypto.private_key' => base64_encode($opendms['private']),
            'services.duck_crypto.public_key' => base64_encode($opendms['public']),
        ]);

        DuckIdentity::create([
            'duck_id' => $duckId,
            'public_key' => base64_encode($duck['public']),
            'first_seen_at' => now(),
        ]);

        MQTT::shouldReceive('publish')
            ->once()
            ->with('hub/command', \Mockery::on(function (string $payload) use ($duckId, $duck, $opendms) {
                $decoded = json_decode($payload, true);
                if ($decoded === null || $decoded['topic'] !== DuckCryptoService::TOPIC_ENCRYPTED_CMD) {
                    return false;
                }

                $blob = base64_decode($decoded['message'], true);
                $nonce = substr($blob, 0, 12);
                $ciphertextAndTag = substr($blob, 12);

                // Simulate the Duck side deriving the same shared key and
                // decrypting, to confirm the ciphertext + AAD are correct.
                $shared = sodium_crypto_scalarmult($duck['private'], $opendms['public']);
                $key = hash_hkdf('sha256', $shared, 32, 'meshbeacon-firmware DuckCrypto');
                $aad = DuckCryptoService::PAPADUCK_DUID.$duckId.chr(DuckCryptoService::TOPIC_ENCRYPTED_CMD);
                $plaintext = sodium_crypto_aead_chacha20poly1305_ietf_decrypt($ciphertextAndTag, $aad, $nonce, $key);

                return $plaintext === 'SOS DITERIMA'
                    && $decoded['target'] === base64_encode($duckId);
            }));

        app(MqttService::class)->sendEncryptedCommand('SOS DITERIMA', $duckId);
    }

    public function test_send_encrypted_command_falls_back_to_plaintext_dcmd_when_identity_unknown(): void
    {
        $duckId = "\xAA\xBB\x00\xCC\xDD\xEE\xFF\x11";

        config([
            'services.duck_crypto.private_key' => base64_encode(random_bytes(32)),
            'services.duck_crypto.public_key' => base64_encode(random_bytes(32)),
        ]);

        MQTT::shouldReceive('publish')
            ->once()
            ->with('hub/command', \Mockery::on(function (string $payload) {
                $decoded = json_decode($payload, true);

                return $decoded !== null
                    && $decoded['topic'] === 22
                    && $decoded['message'] === 'SOS DITERIMA';
            }));

        app(MqttService::class)->sendEncryptedCommand('SOS DITERIMA', $duckId);
    }
}
