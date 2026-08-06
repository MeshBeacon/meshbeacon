<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\TelegramService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Pushes an SOS alert to every responder who has linked their Telegram
 * account. Dispatched from ProcessMqttMessage whenever a hardware or
 * mobile-app SOS is detected — free, no per-message cost, no template
 * approval workflow (unlike WhatsApp Business).
 */
class SendTelegramAlert implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $duckId,
        public string $messageText,
        public ?string $mapUrl = null,
    ) {}

    public function handle(TelegramService $telegram): void
    {
        if (!$telegram->isConfigured()) {
            return;
        }

        $chatIds = User::whereNotNull('telegram_chat_id')->pluck('telegram_chat_id');

        if ($chatIds->isEmpty()) {
            return;
        }

        $text = "\u{1F6A8} <b>SOS Alert</b>\nDuck: <b>{$this->duckId}</b>";

        if ($this->messageText !== '') {
            $text .= "\n{$this->messageText}";
        }

        if ($this->mapUrl) {
            $text .= "\n\u{1F4CD} {$this->mapUrl}";
        }

        foreach ($chatIds as $chatId) {
            $sent = $telegram->sendMessage($chatId, $text);

            if (!$sent) {
                Log::warning('SendTelegramAlert: failed to deliver alert', ['chat_id' => $chatId, 'duck_id' => $this->duckId]);
            }
        }
    }
}
