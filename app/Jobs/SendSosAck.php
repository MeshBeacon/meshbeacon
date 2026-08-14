<?php

namespace App\Jobs;

use App\Services\MqttService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendSosAck implements ShouldQueue
{
    use Queueable;

    /**
     * Total sends (including the first). LoRa is a one-shot transmission with
     * no delivery confirmation, so a single MQTT publish succeeding tells us
     * nothing about whether the Duck actually received it over the air. To
     * make the ACK reliable we unconditionally resend it MAX_ATTEMPTS times,
     * spaced INTERVAL_SECONDS apart, regardless of whether earlier sends
     * appeared to succeed -- there is no way to know one was received, so we
     * always use up all the attempts.
     */
    private const MAX_ATTEMPTS = 3;

    // Matches the firmware's relay-duplicate debounce (5 s, see
    // docs/session-fixes.md §7 in meshbeacon-firmware), which was tuned
    // assuming exactly this 10 s resend spacing -- don't change this without
    // also revisiting that debounce window.
    private const INTERVAL_SECONDS = 10;

    private const ACK_MESSAGE = 'SOS DITERIMA';

    public function __construct(
        protected string $duckId,
        protected int $attempt = 1,
    ) {}

    public function handle(): void
    {
        try {
            // Encrypted via reservedTopic::encrypted_cmd (0x08) when the
            // Duck's public key is already known and OpenDMS's static
            // keypair is configured; otherwise falls back to plaintext
            // dcmd (0x16) -- see MqttService::sendEncryptedCommand().
            app(MqttService::class)->sendEncryptedCommand(self::ACK_MESSAGE, $this->duckId);
            Log::info("SendSosAck: attempt {$this->attempt}/".self::MAX_ATTEMPTS." sent to {$this->duckId}");
        } catch (\Throwable $e) {
            Log::error("SendSosAck: attempt {$this->attempt} failed for {$this->duckId}: {$e->getMessage()}");
        }

        if ($this->attempt >= self::MAX_ATTEMPTS) {
            return;
        }

        self::dispatch($this->duckId, $this->attempt + 1)
            ->delay(now()->addSeconds(self::INTERVAL_SECONDS));
    }
}
