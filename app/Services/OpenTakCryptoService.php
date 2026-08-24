<?php

namespace App\Services;

/**
 * Encrypts/decrypts the MeshBeacon <-> OpenTAKServer plugin MQTT bridge
 * (see docs/OPENTAK_BRIDGE.md). Deliberately mirrors DuckCryptoService's
 * construction (X25519 ECDH via raw sodium_crypto_scalarmult -> HKDF-SHA256
 * -> ChaCha20-Poly1305 IETF AEAD, wire format nonce(12) || ciphertext || tag(16))
 * for consistency across the codebase, but uses its own static keypair and
 * a distinct HKDF info string so the two channels' derived keys can never
 * collide even if a keypair were mistakenly reused between them.
 *
 * Unlike DuckCryptoService (one OpenDMS keypair talking to many per-Duck
 * public keys), this is a single fixed 1:1 peer relationship: one
 * MeshBeacon instance, one OpenTAKServer plugin instance, each holding the
 * other's public key in config up front. There is no TOFU/identity-announce
 * step here.
 */
class OpenTakCryptoService
{
    private const NONCE_LENGTH = 12;
    private const TAG_LENGTH = 16;

    // Distinct from DuckCryptoService::HKDF_INFO on purpose -- see class
    // docblock above.
    private const HKDF_INFO = 'meshbeacon-opentak-bridge';

    public const MSG_TYPE_EVENT = 0x01;
    public const MSG_TYPE_COMMAND = 0x02;

    private const DIRECTION_TO_OPENTAK = 'meshbeacon->opentakserver';
    private const DIRECTION_FROM_OPENTAK = 'opentakserver->meshbeacon';

    /**
     * True only when this app's own keypair AND the peer (OpenTAKServer
     * plugin) public key are all configured. Callers should treat a false
     * return as "bridge unavailable" and skip publishing/processing
     * entirely, same pattern as DuckCryptoService::isConfigured().
     */
    public function isConfigured(): bool
    {
        return $this->isHex32(config('services.opentak.public_key'))
            && filled(config('services.opentak.private_key'))
            && $this->isHex32(config('services.opentak.peer_public_key'));
    }

    private function isHex32(mixed $value): bool
    {
        $value = (string) $value;

        return strlen($value) === 64 && ctype_xdigit($value);
    }

    private function staticPrivateKey(): string
    {
        return base64_decode((string) config('services.opentak.private_key'));
    }

    private function peerPublicKey(): string
    {
        return sodium_hex2bin((string) config('services.opentak.peer_public_key'));
    }

    private function deriveSharedKey(): string
    {
        $shared = sodium_crypto_scalarmult($this->staticPrivateKey(), $this->peerPublicKey());
        $key = hash_hkdf('sha256', $shared, 32, self::HKDF_INFO);
        sodium_memzero($shared);

        return $key;
    }

    /**
     * Build the AAD binding a ciphertext to its direction (who encrypted
     * it) and message type (event vs. command), so a captured ciphertext
     * can't be replayed back in the opposite direction or reinterpreted
     * as a different message type. Both sides must agree on which
     * direction they're building for -- this app always builds
     * DIRECTION_TO_OPENTAK when encrypting and DIRECTION_FROM_OPENTAK when
     * decrypting, and vice versa on the OTS plugin side.
     */
    private function buildAad(string $direction, int $msgType): string
    {
        return $direction.chr($msgType);
    }

    /**
     * Encrypt a plaintext JSON telemetry payload to send to the
     * OpenTAKServer plugin on the event topic.
     *
     * @return string|null base64(nonce || ciphertext || tag), or null if
     *                      the bridge isn't configured.
     */
    public function encryptEvent(string $plaintext): ?string
    {
        return $this->encrypt($plaintext, $this->buildAad(self::DIRECTION_TO_OPENTAK, self::MSG_TYPE_EVENT));
    }

    /**
     * Decrypt a command payload received from the OpenTAKServer plugin on
     * the command topic.
     *
     * @return string|null decrypted plaintext, or null on auth failure,
     *                      malformed input, or missing configuration.
     */
    public function decryptCommand(string $payloadB64): ?string
    {
        return $this->decrypt($payloadB64, $this->buildAad(self::DIRECTION_FROM_OPENTAK, self::MSG_TYPE_COMMAND));
    }

    private function encrypt(string $plaintext, string $aad): ?string
    {
        if (!$this->isConfigured()) {
            return null;
        }

        $key = $this->deriveSharedKey();
        $nonce = random_bytes(self::NONCE_LENGTH);
        $ciphertext = sodium_crypto_aead_chacha20poly1305_ietf_encrypt($plaintext, $aad, $nonce, $key);
        sodium_memzero($key);

        return base64_encode($nonce.$ciphertext);
    }

    private function decrypt(string $payloadB64, string $aad): ?string
    {
        if (!$this->isConfigured()) {
            return null;
        }

        $payload = base64_decode($payloadB64, true);
        if ($payload === false || strlen($payload) < self::NONCE_LENGTH + self::TAG_LENGTH) {
            return null;
        }

        $nonce = substr($payload, 0, self::NONCE_LENGTH);
        $ciphertextAndTag = substr($payload, self::NONCE_LENGTH);

        $key = $this->deriveSharedKey();
        $plaintext = sodium_crypto_aead_chacha20poly1305_ietf_decrypt($ciphertextAndTag, $aad, $nonce, $key);
        sodium_memzero($key);

        return $plaintext === false ? null : $plaintext;
    }
}
