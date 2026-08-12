<?php

namespace App\Console\Commands;

use App\Services\MqttStatus;
use App\Services\WorkerStatus;
use Illuminate\Console\Command;

class ObservabilityHeartbeat extends Command
{
    protected $signature = 'observability:heartbeat {worker : mqtt, queue, or scheduler}';

    protected $description = 'Record a worker heartbeat for operational readiness checks.';

    public function handle(WorkerStatus $workers, MqttStatus $mqtt): int
    {
        $worker = (string) $this->argument('worker');

        if (! in_array($worker, WorkerStatus::workers(), true)) {
            return self::FAILURE;
        }

        $workers->heartbeat($worker);

        if ($worker === 'mqtt') {
            $mqtt->markWorkerHeartbeat();
        }

        return self::SUCCESS;
    }
}
