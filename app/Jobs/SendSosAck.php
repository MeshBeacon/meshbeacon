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
     * Total attempts (including the first) before giving up. The device is on
     * a lossy LoRa link, so a few retries with growing delays give it several
     * chances to receive the ACK — but we stop as soon as one send succeeds,
     * and simply give up (no operator action needed) once attempts run out.
     */
    private const MAX_ATTEMPTS = 3;

    private const BACKOFF_SECONDS = [10, 20];

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
            Log::info("SendSosAck: attempt {$this->attempt} sent to {$this->duckId}");

            return;
        } catch (\Throwable $e) {
            Log::error("SendSosAck: attempt {$this->attempt} failed for {$this->duckId}: {$e->getMessage()}");
        }

        if ($this->attempt >= self::MAX_ATTEMPTS) {
            Log::error("SendSosAck: giving up after {$this->attempt} attempts for {$this->duckId}");

            return;
        }


        self::dispatch($this->duckId, $this->attempt + 1)
            ->delay(now()->addSeconds(self::BACKOFF_SECONDS[$this->attempt - 1]));
    }
}
