<?php

namespace App\Console\Commands;

use App\Models\GpsPoll;
use App\Services\MqttService;
use Illuminate\Console\Command;

class PollGps extends Command
{
    const INTERVAL_MINUTES = 1;

    protected $signature = 'gps:poll';
    protected $description = 'Send GPS requests to all ducks that have auto-polling enabled and are due.';

    public function handle(MqttService $mqttService): void
    {
        $due = GpsPoll::due()->get();

        foreach ($due as $poll) {
            // See StatusController::requestGps() -- the firmware only
            // honors GPS requests sent via authenticated encrypted_cmd.
            $encrypted = $mqttService->sendEncryptedCommand('CMD:GPS_REQUEST', $poll->duck_id);

            $poll->next_run_at = now()->addMinutes($poll->interval_minutes ?: self::INTERVAL_MINUTES);
            $poll->save();

            if ($encrypted) {
                $this->line("GPS poll dispatched (encrypted) to {$poll->duck_id}. Next run: {$poll->next_run_at}");
            } else {
                $this->line("GPS poll SKIPPED for {$poll->duck_id}: no authenticated channel available (identity unknown or OpenDMS keypair not configured). Next run: {$poll->next_run_at}");
            }
        }

        if ($due->isEmpty()) {
            $this->line('No GPS polls due.');
        }
    }
}
