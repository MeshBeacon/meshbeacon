<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

use App\Models\TakLog;
use Illuminate\Support\Facades\Log;

class ProcessTakLog implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $message
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $data = json_decode($this->message, true, 512, JSON_THROW_ON_ERROR);

            TakLog::create([
                'device_id' => $data['device_id'] ?? 'unknown',
                'target' => $data['target'] ?? 'unknown',
                'cot_xml' => $data['cot_xml'] ?? '',
                'created_at' => isset($data['timestamp']) ? \Carbon\Carbon::parse($data['timestamp']) : now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to process TAK log', ['error' => $e->getMessage()]);
        }
    }
}
