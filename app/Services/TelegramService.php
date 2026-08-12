<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Thin wrapper around the Telegram Bot API (https://core.telegram.org/bots/api).
 * Used to push SOS alerts to responders who have linked their Telegram account,
 * and to manage the webhook that receives /start linking messages.
 */
class TelegramService
{
    private function botToken(): string
    {
        return (string) config('services.telegram.bot_token', '');
    }

    public function isConfigured(): bool
    {
        return $this->botToken() !== '';
    }

    public function botUsername(): string
    {
        return (string) config('services.telegram.bot_username', '');
    }

    /**
     * Send a message to a single chat. Returns true on success.
     */
    public function sendMessage(string $chatId, string $text): bool
    {
        if (!$this->isConfigured()) {
            return false;
        }

        try {
            $response = Http::timeout(10)->asForm()->post(
                "https://api.telegram.org/bot{$this->botToken()}/sendMessage",
                [
                    'chat_id'                  => $chatId,
                    'text'                     => $text,
                    'parse_mode'               => 'HTML',
                    'disable_web_page_preview' => false,
                ]
            );

            if (!$response->successful()) {
                Log::warning('TelegramService: sendMessage failed', [
                    'chat_id' => $chatId,
                    'status'  => $response->status(),
                    'body'    => $response->body(),
                ]);
            }

            \App\Models\TelegramLog::create([
                'chat_id' => $chatId,
                'text'    => $text,
                'status'  => $response->successful() ? 'sent' : 'failed',
            ]);

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error('TelegramService: sendMessage exception', ['error' => $e->getMessage()]);

            \App\Models\TelegramLog::create([
                'chat_id' => $chatId,
                'text'    => $text,
                'status'  => 'error',
            ]);

            return false;
        }
    }

    /**
     * Registers the webhook URL with Telegram, protected by a secret token
     * that Telegram will echo back in the X-Telegram-Bot-Api-Secret-Token
     * header on every webhook call.
     */
    public function setWebhook(string $url, string $secretToken): array
    {
        $response = Http::timeout(10)->asForm()->post(
            "https://api.telegram.org/bot{$this->botToken()}/setWebhook",
            [
                'url'          => $url,
                'secret_token' => $secretToken,
            ]
        );

        return $response->json() ?? [];
    }
}
