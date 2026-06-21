<?php

namespace App\Jobs;

use App\Services\MqttService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendSosAck implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected string $duckId,
        protected int $attempt = 1,
    ) {}

    public function handle(): void
    {
        try {
            app(MqttService::class)->sendCommand('SOS DITERIMA', $this->duckId, 22);
            Log::info("SendSosAck: attempt {$this->attempt} sent to {$this->duckId}");
        } catch (\Throwable $e) {
            Log::error("SendSosAck: attempt {$this->attempt} failed for {$this->duckId}: {$e->getMessage()}");
        }
    }
}
