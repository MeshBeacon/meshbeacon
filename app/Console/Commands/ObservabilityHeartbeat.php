<?php

namespace App\Console\Commands;

use App\Services\WorkerStatus;
use Illuminate\Console\Command;

class ObservabilityHeartbeat extends Command
{
    protected $signature = 'observability:heartbeat {worker : mqtt, queue, or scheduler}';

    protected $description = 'Record a worker heartbeat for operational readiness checks.';

    /**
     * This only proves the worker's OS process is alive. It intentionally does
     * not touch MqttStatus: that must only reflect a genuine broker connection,
     * which MqttSubscribe reports for itself from inside the MQTT client loop.
     */
    public function handle(WorkerStatus $workers): int
    {
        $worker = (string) $this->argument('worker');

        if (! in_array($worker, WorkerStatus::workers(), true)) {
            return self::FAILURE;
        }

        $workers->heartbeat($worker);

        return self::SUCCESS;
    }
}
