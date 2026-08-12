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

    /**
     * Total attempts (including the first) before giving up on a recipient.
     * Retries are scoped to only the chat IDs that failed, so recipients who
     * already received the alert are never re-sent it. Once attempts run
     * out we just log and stop — no operator action needed.
     */
    private const MAX_ATTEMPTS = 4;

    private const BACKOFF_SECONDS = [15, 30, 60];

    public function __construct(
        public string $duckId,
        public string $messageText,
        public ?string $mapUrl = null,
        protected ?array $chatIds = null,
        protected int $attempt = 1,
    ) {}

    public function handle(TelegramService $telegram): void
    {
        if (!$telegram->isConfigured()) {
            return;
        }

        $chatIds = $this->chatIds ?? User::whereNotNull('telegram_chat_id')->pluck('telegram_chat_id')->all();

        if (empty($chatIds)) {
            return;
        }

        $text = "\u{1F6A8} <b>SOS Alert</b>\nDuck: <b>{$this->duckId}</b>";

        if ($this->messageText !== '') {
            $text .= "\n{$this->messageText}";
        }

        if ($this->mapUrl) {
            $text .= "\n\u{1F4CD} {$this->mapUrl}";
        }

        $failedChatIds = [];

        foreach ($chatIds as $chatId) {
            $sent = $telegram->sendMessage($chatId, $text);

            if (!$sent) {
                Log::warning('SendTelegramAlert: failed to deliver alert', ['chat_id' => $chatId, 'duck_id' => $this->duckId]);
                $failedChatIds[] = $chatId;
            }
        }

        if ($failedChatIds === []) {
            return;
        }

        if ($this->attempt >= self::MAX_ATTEMPTS) {
            Log::error("SendTelegramAlert: giving up after {$this->attempt} attempts for {$this->duckId}", ['chat_ids' => $failedChatIds]);

            return;
        }

        self::dispatch($this->duckId, $this->messageText, $this->mapUrl, $failedChatIds, $this->attempt + 1)
            ->delay(now()->addSeconds(self::BACKOFF_SECONDS[$this->attempt - 1]));
    }
}
