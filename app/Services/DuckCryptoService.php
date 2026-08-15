<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Mirrors meshbeacon-firmware's src/security/DuckCrypto.{h,cpp} symmetric
 * session construction (encryptWithPeer/decryptFromPeer), bit-for-bit:
 * X25519 ECDH (raw sodium_crypto_scalarmult, NOT sodium_crypto_box_seal --
 * that uses XSalsa20-Poly1305 and is wire-incompatible) -> HKDF-SHA256 ->
 * ChaCha20-Poly1305 IETF AEAD with a 96-bit random nonce.
 *
 * Wire format for the encrypted blob: nonce(12) || ciphertext(N) || tag(16).
 * sodium's *_ietf_encrypt() already appends the 16-byte Poly1305 tag to the
 * end of its ciphertext output, so `$nonce . $ciphertext` is exactly this
 * layout with no further packing needed.
 *
 * See docs/crypto-design.tex (meshbeacon-firmware repo), section
 * "OpenDMS -> Duck (operator-initiated downlink)".
 */
class DuckCryptoService
{
    private const NONCE_LENGTH = 12;
    private const TAG_LENGTH = 16;
    private const EPHEMERAL_PUBLIC_KEY_LENGTH = 32;

    // Must match DuckCrypto.cpp's HKDF_INFO constant exactly.
    private const HKDF_INFO = 'meshbeacon-firmware DuckCrypto';

    // Must match CdpPacket.h's reservedTopic values exactly -- these are
    // the on-air (cleartext header) topic bytes used in buildHeaderAad().
    public const TOPIC_ENCRYPTED_CMD = 0x08;
    public const TOPIC_SEALED_UPLINK = 0x09;
    public const TOPIC_ENCRYPTED_DATA = 0x0B;

    // Sketch-level (not CDP-reserved) app topic used by
    // examples/Basic-Ducks/Seeed/WioTrackerL1/MamaDuck.ino's "Emergency
    // broadcast" handler (case 24). Unlike the reservedTopic values above,
    // this is an app-specific convention, not part of the CDP framework.
    public const TOPIC_BROADCAST = 24;

    // Marker byte prefixed to a group-key-authenticated broadcast payload,
    // must match MamaDuck.ino's BROADCAST_AUTH_MARKER exactly.
    private const BROADCAST_AUTH_MARKER = "\xE8";

    // 4-byte big-endian monotonic counter length, must match
    // MamaDuck.ino's BROADCAST_COUNTER_LENGTH exactly.
    private const BROADCAST_COUNTER_LENGTH = 4;

    // Persisted (never expires) replay-protection counter for Emergency
    // Broadcasts. OpenDMS is the sole sender, so a single global counter
    // (not per-DUID) is sufficient -- see authenticateGroupBroadcast().
    private const BROADCAST_COUNTER_CACHE_KEY = 'meshbeacon:duckcrypto:broadcast_counter';

    /**
     * PAPADUCK_DUID (src/CdpPacket.h): the fixed all-zero DUID used as a
     * stand-in for "OpenDMS" in AAD construction, on both sides of the
     * link -- OpenDMS has no DUID of its own (it's identified only by its
     * static X25519 keypair, not a mesh identity), so this fixed 8-byte
     * placeholder is used wherever OpenDMS is conceptually the sender or
     * recipient of a header-bound AEAD payload: sendSealedData()'s default
     * target (uplink, OpenDMS as recipient) and encrypted_cmd's AAD
     * (downlink, OpenDMS as sender -- see MamaDuck.h's encrypted_cmd case,
     * which uses this same fixed value rather than whichever physical hub
     * happened to relay the packet on-air).
     */
    public const PAPADUCK_DUID = "\x00\x00\x00\x00\x00\x00\x00\x00";

    /**
     * True only when this OpenDMS instance's static X25519 keypair is
     * configured. Callers should treat a false return as "encryption
     * unavailable" and fall back to their existing unencrypted behavior.
     *
     * public_key is also format-checked (64 hex chars) since it's meant to
     * be pasted verbatim into meshbeacon-firmware's
     * OPENDMS_STATIC_PUBLIC_KEY_HEX build flag -- catching a stray
     * base64 value here (from before this was switched to hex) is cheaper
     * than debugging a firmware that silently fails to decrypt.
     */
    public function isConfigured(): bool
    {
        $publicKey = (string) config('services.duck_crypto.public_key');

        return filled(config('services.duck_crypto.private_key'))
            && strlen($publicKey) === 64
            && ctype_xdigit($publicKey);
    }

    private function staticPrivateKey(): string
    {
        return base64_decode((string) config('services.duck_crypto.private_key'));
    }

    private function deriveSharedKey(string $peerPublicKey): string
    {
        $shared = sodium_crypto_scalarmult($this->staticPrivateKey(), $peerPublicKey);
        $key = hash_hkdf('sha256', $shared, 32, self::HKDF_INFO);
        sodium_memzero($shared);

        return $key;
    }

    /**
     * Build the additional-authenticated-data bytes that bind an
     * encrypted/sealed payload to its cleartext CDP header, so a relay
     * can't splice a captured ciphertext onto a different sender,
     * recipient, or topic and still have it authenticate. Must match
     * Duck::buildHeaderAad() (meshbeacon-firmware's src/Ducks/Duck.h)
     * exactly, byte for byte, or decryption will fail auth.
     *
     * Deliberately excludes muid, hopCount, and dcrc: muid is assigned by
     * the firmware router *after* the ciphertext is built, and
     * hopCount/dcrc mutate on every relay hop -- binding either would
     * make a legitimately multi-hop-relayed packet fail to decrypt at its
     * final destination. sduid/dduid/topic are fixed by the original
     * sender and never rewritten in transit.
     *
     * @param  string  $sduid  raw 8-byte sender DUID
     * @param  string  $dduid  raw 8-byte destination DUID
     * @param  int  $topic  the on-air CDP topic byte, e.g. self::TOPIC_SEALED_UPLINK
     */
    public function buildHeaderAad(string $sduid, string $dduid, int $topic): string
    {
        return $sduid.$dduid.chr($topic);
    }

    /**
     * Encrypt a plaintext command to a specific Duck, using this OpenDMS
     * instance's static private key + the Duck's known static public key
     * (ECDH) -- mirrors DuckCrypto::encryptWithPeer() on the firmware side.
     *
     * @param  string  $duckPublicKeyB64  base64-encoded 32-byte X25519 public key
     * @return string|null base64(nonce || ciphertext || tag), or null if
     *                      OpenDMS's static keypair isn't configured.
     */
    public function encryptToDuck(string $duckPublicKeyB64, string $plaintext, string $aad = ''): ?string
    {
        if (!$this->isConfigured()) {
            return null;
        }

        $duckPublicKey = base64_decode($duckPublicKeyB64);
        $key = $this->deriveSharedKey($duckPublicKey);

        $nonce = random_bytes(self::NONCE_LENGTH);
        $ciphertext = sodium_crypto_aead_chacha20poly1305_ietf_encrypt($plaintext, $aad, $nonce, $key);
        sodium_memzero($key);

        return base64_encode($nonce.$ciphertext);
    }

    /**
     * Decrypt a payload received from a specific Duck, using the same
     * ECDH-symmetric construction -- mirrors DuckCrypto::decryptFromPeer()
     * on the firmware side.
     *
     * @param  string  $duckPublicKeyB64  base64-encoded 32-byte X25519 public key
     * @param  string  $payloadB64        base64(nonce || ciphertext || tag)
     * @return string|null decrypted plaintext, or null on auth failure,
     *                      malformed input, or missing configuration.
     */
    public function decryptFromDuck(string $duckPublicKeyB64, string $payloadB64, string $aad = ''): ?string
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

        $duckPublicKey = base64_decode($duckPublicKeyB64);
        $key = $this->deriveSharedKey($duckPublicKey);

        $plaintext = sodium_crypto_aead_chacha20poly1305_ietf_decrypt($ciphertextAndTag, $aad, $nonce, $key);
        sodium_memzero($key);

        return $plaintext === false ? null : $plaintext;
    }

    /**
     * Decrypt a one-way sealed uplink message from a Duck, mirroring
     * DuckCrypto::sealToStatic() / Duck::sendSealedData() on the firmware
     * side (see reservedTopic::sealed_uplink in CdpPacket.h). Unlike
     * decryptFromDuck(), this does NOT need a stored Duck public key --
     * the sender's one-time ephemeral public key travels with the message
     * itself, so any Duck can seal to us anonymously without a prior
     * identity exchange.
     *
     * @param  string  $payloadB64  base64(ephemeralPublicKey(32) || nonce(12) || ciphertext(N) || tag(16))
     * @return string|null decrypted plaintext payload only -- the
     *                      app-level topic is NOT folded into this
     *                      ciphertext, it arrives separately as a
     *                      cleartext (AAD-authenticated) prefix byte on
     *                      the wire (see Duck::sendSealedData()), and
     *                      must be passed in as $aad's topic component
     *                      via buildHeaderAad() -- or null on auth
     *                      failure, malformed input, or missing
     *                      configuration.
     */
    public function unsealFromDuck(string $payloadB64, string $aad = ''): ?string
    {
        if (!$this->isConfigured()) {
            return null;
        }

        $payload = base64_decode($payloadB64, true);
        $minLength = self::EPHEMERAL_PUBLIC_KEY_LENGTH + self::NONCE_LENGTH + self::TAG_LENGTH;
        if ($payload === false || strlen($payload) < $minLength) {
            return null;
        }

        $ephemeralPublicKey = substr($payload, 0, self::EPHEMERAL_PUBLIC_KEY_LENGTH);
        $nonce = substr($payload, self::EPHEMERAL_PUBLIC_KEY_LENGTH, self::NONCE_LENGTH);
        $ciphertextAndTag = substr($payload, self::EPHEMERAL_PUBLIC_KEY_LENGTH + self::NONCE_LENGTH);

        $key = $this->deriveSharedKey($ephemeralPublicKey);

        $plaintext = sodium_crypto_aead_chacha20poly1305_ietf_decrypt($ciphertextAndTag, $aad, $nonce, $key);
        sodium_memzero($key);

        return $plaintext === false ? null : $plaintext;
    }

    /**
     * True only when this deployment's pre-shared mesh group symmetric key
     * (services.duck_crypto.mesh_group_key) is configured -- callers
     * should treat a false return as "group encryption unavailable" and
     * fall back to their existing unencrypted behavior, same as
     * isConfigured() above.
     *
     * Format-checked (64 hex chars) for the same reason as the OpenDMS
     * public_key check: it's meant to be pasted verbatim into
     * meshbeacon-firmware's MESH_GROUP_KEY_HEX build flag.
     */
    public function isGroupConfigured(): bool
    {
        $key = (string) config('services.duck_crypto.mesh_group_key');

        return strlen($key) === 64 && ctype_xdigit($key);
    }

    private function groupKey(): string
    {
        return hex2bin((string) config('services.duck_crypto.mesh_group_key'));
    }

    /**
     * Authenticate (but do NOT encrypt) an Emergency Broadcast payload
     * with the deployment's mesh group key -- the message stays as
     * legible cleartext on the wire (deliberately: a life-safety alert
     * should be readable by anyone in range, including devices without
     * the group key), but only someone holding the group key can produce
     * a tag that verifies, so forged broadcasts are still rejected by any
     * Duck that has the key configured.
     *
     * Implemented by calling the AEAD cipher with the message passed as
     * additional authenticated data (authenticated, not encrypted) and an
     * empty plaintext -- a standard, secure way to get a MAC (not
     * confidentiality) out of an AEAD primitive. No X25519 ECDH either:
     * the group key is used directly, mirroring
     * duckcrypto::encryptWithGroupKey() on the firmware side. This can't
     * reuse encrypted_cmd's point-to-point channel at all: that's a
     * different shared secret per Duck (static-static ECDH between
     * OpenDMS and ONE Duck's identity), so it can never produce a single
     * tag every Duck in a deployment can verify -- the group key is the
     * only broadcast-capable authenticated channel this firmware has.
     *
     * Mirrors MamaDuck.ino's verifyBroadcastMac()/BROADCAST_AUTH_MARKER
     * exactly, bit for bit -- including binding the AAD to the fixed
     * PAPADUCK_DUID placeholder rather than any physical gateway's
     * transient DUID, which OpenDMS cannot predict (same convention
     * already established for encrypted_cmd's AAD).
     *
     * Wire format: marker(1) || nonce(12) || counter(4, big-endian) ||
     * message(N, cleartext) || tag(16).
     *
     * Replay protection: a monotonic counter (persisted in cache under
     * self::BROADCAST_COUNTER_CACHE_KEY, since OpenDMS is the sole sender
     * of Emergency Broadcasts) is incremented on every call and bound
     * into the AAD alongside the message. MamaDuck.ino's
     * verifyBroadcastMac() rejects any broadcast whose counter is not
     * strictly greater than the last one it accepted, so a captured
     * broadcast (e.g. a stale "all clear") cannot be replayed verbatim.
     *
     * @return string|null base64(marker || nonce || counter || message ||
     *                      tag), or null if the mesh group key isn't
     *                      configured.
     */
    public function authenticateGroupBroadcast(string $message): ?string
    {
        if (!$this->isGroupConfigured()) {
            return null;
        }

        if (!Cache::has(self::BROADCAST_COUNTER_CACHE_KEY)) {
            Cache::forever(self::BROADCAST_COUNTER_CACHE_KEY, 0);
        }
        $counter = Cache::increment(self::BROADCAST_COUNTER_CACHE_KEY);
        $counterBytes = pack('N', $counter);

        $aad = chr(self::TOPIC_BROADCAST).self::PAPADUCK_DUID.$counterBytes.$message;
        $nonce = random_bytes(self::NONCE_LENGTH);
        $key = $this->groupKey();
        $tag = sodium_crypto_aead_chacha20poly1305_ietf_encrypt('', $aad, $nonce, $key);
        sodium_memzero($key);

        return base64_encode(self::BROADCAST_AUTH_MARKER.$nonce.$counterBytes.$message.$tag);
    }
}
