<?php

namespace Tests\Feature;

use App\Models\DuckIdentity;
use App\Services\DuckCryptoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verifies the OpenDMS-side crypto construction added alongside
 * meshbeacon-firmware's DuckCrypto module (see docs/crypto-design.tex,
 * "OpenDMS -> Duck (operator-initiated downlink)"), without needing real
 * firmware: X25519 ECDH is symmetric, so we can simulate "the Duck side"
 * with a second raw sodium keypair and confirm both directions decrypt
 * correctly using the exact wire format (nonce||ciphertext||tag) and HKDF
 * info string the firmware uses.
 */
class DuckCryptoServiceTest extends TestCase
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

    public function test_migration_creates_duck_identities_table(): void
    {
        $identity = DuckIdentity::create([
            'duck_id' => 'AABBCCDD',
            'public_key' => base64_encode(random_bytes(32)),
            'first_seen_at' => now(),
        ]);

        $this->assertDatabaseHas('duck_identities', ['duck_id' => 'AABBCCDD']);
        $this->assertNotNull($identity->first_seen_at);
    }

    public function test_encrypt_to_duck_is_decryptable_by_the_ducks_private_key(): void
    {
        $opendms = $this->rawX25519KeyPair();
        $duck = $this->rawX25519KeyPair();

        config([
            'services.duck_crypto.private_key' => base64_encode($opendms['private']),
            'services.duck_crypto.public_key' => bin2hex($opendms['public']),
        ]);

        $plaintext = 'SOS DITERIMA';
        $blob = base64_decode(
            app(DuckCryptoService::class)->encryptToDuck(base64_encode($duck['public']), $plaintext)
        );

        // Simulate the Duck side decrypting with its own private key +
        // OpenDMS's public key -- ECDH is symmetric, so this must derive
        // the identical shared secret/key the service used to encrypt.
        $nonce = substr($blob, 0, 12);
        $ciphertextAndTag = substr($blob, 12);
        $shared = sodium_crypto_scalarmult($duck['private'], $opendms['public']);
        $key = hash_hkdf('sha256', $shared, 32, 'meshbeacon-firmware DuckCrypto');

        $decrypted = sodium_crypto_aead_chacha20poly1305_ietf_decrypt($ciphertextAndTag, '', $nonce, $key);

        $this->assertSame($plaintext, $decrypted);
    }

    public function test_decrypt_from_duck_verifies_a_message_the_duck_encrypted(): void
    {
        $opendms = $this->rawX25519KeyPair();
        $duck = $this->rawX25519KeyPair();

        config([
            'services.duck_crypto.private_key' => base64_encode($opendms['private']),
            'services.duck_crypto.public_key' => bin2hex($opendms['public']),
        ]);

        // Simulate the Duck side encrypting with its own private key +
        // OpenDMS's public key.
        $shared = sodium_crypto_scalarmult($duck['private'], $opendms['public']);
        $key = hash_hkdf('sha256', $shared, 32, 'meshbeacon-firmware DuckCrypto');
        $nonce = random_bytes(12);
        $plaintext = 'MSG,URGENCY:2,TEXT:help';
        $ciphertext = sodium_crypto_aead_chacha20poly1305_ietf_encrypt($plaintext, '', $nonce, $key);
        $payloadB64 = base64_encode($nonce.$ciphertext);

        $decrypted = app(DuckCryptoService::class)->decryptFromDuck(base64_encode($duck['public']), $payloadB64);

        $this->assertSame($plaintext, $decrypted);
    }

    public function test_returns_null_when_not_configured(): void
    {
        config([
            'services.duck_crypto.private_key' => '',
            'services.duck_crypto.public_key' => '',
        ]);

        $service = app(DuckCryptoService::class);

        $this->assertFalse($service->isConfigured());
        $this->assertNull($service->encryptToDuck(base64_encode(random_bytes(32)), 'hello'));
        $this->assertNull($service->decryptFromDuck(base64_encode(random_bytes(32)), base64_encode(random_bytes(40))));
    }

    public function test_unseal_from_duck_verifies_a_message_sealed_with_a_fresh_ephemeral_key(): void
    {
        $opendms = $this->rawX25519KeyPair();

        config([
            'services.duck_crypto.private_key' => base64_encode($opendms['private']),
            'services.duck_crypto.public_key' => bin2hex($opendms['public']),
        ]);

        // Simulate Duck::sendSealedData()/duckcrypto::sealToStatic(): a
        // fresh, one-time ephemeral X25519 keypair (not the Duck's own
        // long-term identity) is used for the ECDH, and its public half
        // travels with the message so OpenDMS can derive the same shared
        // secret without needing to already know the sender.
        $ephemeral = $this->rawX25519KeyPair();
        $shared = sodium_crypto_scalarmult($ephemeral['private'], $opendms['public']);
        $key = hash_hkdf('sha256', $shared, 32, 'meshbeacon-firmware DuckCrypto');
        $nonce = random_bytes(12);
        // First plaintext byte is the original app-level topic (e.g.
        // topics::alert), per Duck::sendSealedData()'s embed-topic scheme.
        $plaintext = chr(0x14).'SOS,LAT:1.234,LON:5.678';

        $service = app(DuckCryptoService::class);
        $sduid = random_bytes(8);
        $dduid = str_repeat("\xFF", 8); // PAPADUCK_DUID
        $aad = $service->buildHeaderAad($sduid, $dduid, DuckCryptoService::TOPIC_SEALED_UPLINK);

        $ciphertext = sodium_crypto_aead_chacha20poly1305_ietf_encrypt($plaintext, $aad, $nonce, $key);
        $payloadB64 = base64_encode($ephemeral['public'].$nonce.$ciphertext);

        $decrypted = $service->unsealFromDuck($payloadB64, $aad);
        $this->assertSame($plaintext, $decrypted);

        // Splicing the ciphertext onto a different sduid (e.g. an
        // attacker replaying a captured message while claiming to be a
        // different Duck) must now fail authentication.
        $wrongAad = $service->buildHeaderAad(random_bytes(8), $dduid, DuckCryptoService::TOPIC_SEALED_UPLINK);
        $this->assertNull($service->unsealFromDuck($payloadB64, $wrongAad));
    }

    public function test_unseal_from_duck_returns_null_on_malformed_or_tampered_input(): void
    {
        $opendms = $this->rawX25519KeyPair();

        config([
            'services.duck_crypto.private_key' => base64_encode($opendms['private']),
            'services.duck_crypto.public_key' => bin2hex($opendms['public']),
        ]);

        $service = app(DuckCryptoService::class);

        // Too short to even contain ephemeral pubkey + nonce + tag.
        $this->assertNull($service->unsealFromDuck(base64_encode(random_bytes(20))));

        // Well-formed length, but garbage bytes -- must fail Poly1305 auth.
        $this->assertNull($service->unsealFromDuck(base64_encode(random_bytes(32 + 12 + 16 + 10))));
    }
}
