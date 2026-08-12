<?php

namespace App\Console\Commands;

use App\Services\OperationalStatus;
use Illuminate\Console\Command;

class ObservabilityCheck extends Command
{
    protected $signature = 'observability:check
        {component : app, migrations, or worker}
        {--worker= : Worker name when component is worker}';

    protected $description = 'Check a MeshBeacon runtime component for container health checks.';

    public function handle(OperationalStatus $status): int
    {
        return match ($this->argument('component')) {
            'app' => $status->applicationReady() ? self::SUCCESS : self::FAILURE,
            'migrations' => $status->migrationsReady() ? self::SUCCESS : self::FAILURE,
            'worker' => $this->checkWorker($status),
            default => $this->invalidComponent(),
        };
    }

    private function checkWorker(OperationalStatus $status): int
    {
        $worker = (string) $this->option('worker');

        if ($worker === '') {
            $this->components->error('The --worker option is required.');

            return self::FAILURE;
        }

        return $status->workerReady($worker) ? self::SUCCESS : self::FAILURE;
    }

    private function invalidComponent(): int
    {
        $this->components->error('Unknown health component.');

        return self::FAILURE;
    }
}
