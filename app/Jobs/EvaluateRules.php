<?php

namespace App\Jobs;

use App\Models\ClusterData;
use App\Models\Rule;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class EvaluateRules implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public ClusterData $record)
    {
    }

    public function handle(): void
    {
        $rules = Rule::where('is_active', true)->get();

        foreach ($rules as $rule) {
            $triggered = false;

            if ($rule->condition === 'battery_below' && $this->record->gps_batt !== null) {
                $triggered = $this->record->gps_batt < $rule->threshold;
            } elseif ($rule->condition === 'rssi_below' && $this->record->gps_rssi !== null) {
                $triggered = $this->record->gps_rssi < $rule->threshold;
            }

            if ($triggered) {
                Log::info("Rule '{$rule->name}' triggered for duck {$this->record->duck_id}");

                if ($rule->action === 'telegram_alert') {
                    $message = "⚠️ *RULE TRIGGERED:* {$rule->name}\nDuck: `{$this->record->duck_id}`";
                    SendTelegramAlert::dispatch($this->record->duck_id, $message, $this->record->map_url);
                }
            }
        }
    }
}
