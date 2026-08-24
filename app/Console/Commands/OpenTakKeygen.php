<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Generates a static X25519 keypair for the MeshBeacon <-> OpenTAKServer
 * encrypted bridge (see docs/OPENTAK_BRIDGE.md and OpenTakCryptoService).
 * This only prints the keys -- it never writes .env itself, since that
 * file may not be plainly writable/reloadable depending on deployment
 * (Docker secrets, k8s env injection, etc.) and silently overwriting an
 * existing key would break a working link.
 */
class OpenTakKeygen extends Command
{
    protected $signature = 'opentak:keygen';

    protected $description = 'Generate a static X25519 keypair for the OpenTAKServer encrypted MQTT bridge';

    public function handle(): int
    {
        if (filled(config('services.opentak.private_key')) || filled(config('services.opentak.public_key'))) {
            $this->components->warn('OPENTAK_BRIDGE_PRIVATE_KEY / OPENTAK_BRIDGE_PUBLIC_KEY are already set in this environment.');
            if (!$this->components->confirm('Generate a new keypair anyway? The old one will stop working until you update .env with the new values below.')) {
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
        $this->line("OPENTAK_BRIDGE_PRIVATE_KEY={$privateKeyB64}");
        $this->line("OPENTAK_BRIDGE_PUBLIC_KEY={$publicKeyHex}");
        $this->newLine();
        $this->components->info("Share only the public key with the OpenTAKServer plugin operator: {$publicKeyHex}");
        $this->components->warn('The private key must never leave this server. Back it up securely -- losing it requires generating a new keypair and re-exchanging public keys with the OTS plugin.');

        return self::SUCCESS;
    }
}
