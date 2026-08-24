<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Generates this OpenDMS instance's static X25519 keypair for
 * meshbeacon-firmware's DuckCrypto mesh end-to-end encryption (see
 * docs/crypto-design.tex in meshbeacon-firmware and DuckCryptoService).
 * This only prints the keys -- it never writes .env itself, since that
 * file may not be plainly writable/reloadable depending on deployment
 * (Docker secrets, k8s env injection, etc.) and silently overwriting an
 * existing key would break every already-fielded Duck.
 */
class DuckKeygen extends Command
{
    protected $signature = 'duck:keygen';

    protected $description = 'Generate a static X25519 keypair for meshbeacon-firmware DuckCrypto mesh encryption';

    public function handle(): int
    {
        if (filled(config('services.duck_crypto.private_key')) || filled(config('services.duck_crypto.public_key'))) {
            $this->components->warn('DUCK_CRYPTO_PRIVATE_KEY / DUCK_CRYPTO_PUBLIC_KEY are already set in this environment.');
            if (!$this->components->confirm('Generate a new keypair anyway? Every already-fielded Duck will need to be re-flashed with the new public key before it can communicate again.')) {
                return self::SUCCESS;
            }
        }

        $keypair = sodium_crypto_box_keypair();
        $privateKey = sodium_crypto_box_secretkey($keypair);
        $publicKey = sodium_crypto_box_publickey($keypair);
        sodium_memzero($keypair);

        $privateKeyB64 = base64_encode($privateKey);
        $publicKeyHex = bin2hex($publicKey);
        sodium_memzero($privateKey);

        $this->newLine();
        $this->line('Add these to this MeshBeacon instance\'s .env:');
        $this->newLine();
        $this->line("DUCK_CRYPTO_PRIVATE_KEY={$privateKeyB64}");
        $this->line("DUCK_CRYPTO_PUBLIC_KEY={$publicKeyHex}");
        $this->newLine();
        $this->components->info("Flash only the public key into field devices, as meshbeacon-firmware's OPENDMS_STATIC_PUBLIC_KEY_HEX build flag: {$publicKeyHex}");
        $this->components->warn('The private key must never leave this server or be flashed into a Duck. Back it up securely -- losing it requires generating a new keypair and re-flashing every already-fielded device with the new public key.');

        return self::SUCCESS;
    }
}
