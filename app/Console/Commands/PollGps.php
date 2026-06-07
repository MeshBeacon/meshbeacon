<?php

namespace App\Console\Commands;

use App\Models\GpsPoll;
use App\Services\MqttService;
use Illuminate\Console\Command;

class PollGps extends Command
{
    const INTERVAL_MINUTES = 5;

    protected $signature = 'gps:poll';
    protected $description = 'Send GPS requests to all ducks that have auto-polling enabled and are due.';

    public function handle(MqttService $mqttService): void
    {
        $due = GpsPoll::due()->get();

        foreach ($due as $poll) {
            $mqttService->sendCommand(
                message: 'null',
                target:  $poll->duck_id,
                topic:   234,
            );

            $poll->next_run_at = now()->addMinutes(self::INTERVAL_MINUTES);
            $poll->save();

            $this->line("GPS poll dispatched to {$poll->duck_id}. Next run: {$poll->next_run_at}");
        }

        if ($due->isEmpty()) {
            $this->line('No GPS polls due.');
        }
    }
}
