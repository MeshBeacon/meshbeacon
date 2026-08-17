<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * TOFU (trust-on-first-use) store of each Duck's long-term X25519
     * static public key, keyed by DUID. Populated the first time a given
     * duck_id is seen carrying its public key (see
     * app/Jobs/ProcessMqttMessage.php), and consulted by
     * App\Services\DuckCryptoService to encrypt operator downlink
     * commands (e.g. SendSosAck) to a specific device.
     */
    public function up(): void
    {
        Schema::create('duck_identities', function (Blueprint $table) {
            $table->id();
            $table->string('duck_id')->unique();
            $table->string('public_key'); // base64-encoded 32-byte X25519 public key
            $table->timestamp('first_seen_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('duck_identities');
    }
};
