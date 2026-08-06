<?php

namespace App\Console\Commands;

use App\Services\TelegramService;
use Illuminate\Console\Command;

class TelegramSetWebhook extends Command
{
    protected $signature = 'telegram:set-webhook';

    protected $description = 'Register this server\'s Telegram webhook URL with the Bot API';

    public function handle(TelegramService $telegram): int
    {
        if (!$telegram->isConfigured()) {
            $this->error('TELEGRAM_BOT_TOKEN is not set — configure it in .env first.');

            return self::FAILURE;
        }

        $secret = (string) config('services.telegram.webhook_secret', '');

        if ($secret === '') {
            $this->error('TELEGRAM_WEBHOOK_SECRET is not set — configure it in .env first.');

            return self::FAILURE;
        }

        $url = rtrim(config('app.url'), '/') . '/telegram/webhook/' . $secret;

        if (!str_starts_with($url, 'https://')) {
            $this->warn("Warning: {$url} is not HTTPS — Telegram requires an HTTPS webhook URL and will reject this.");
        }

        $result = $telegram->setWebhook($url, $secret);

        if ($result['ok'] ?? false) {
            $this->info("Webhook registered: {$url}");

            return self::SUCCESS;
        }

        $this->error('Failed to register webhook: ' . ($result['description'] ?? 'unknown error'));

        return self::FAILURE;
    }
}
