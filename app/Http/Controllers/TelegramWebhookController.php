<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Receives updates from Telegram (webhook mode). The only update we care
 * about is a user messaging the bot with the one-time linking code shown
 * in their profile settings, either via the /start deep link or a plain
 * text message.
 */
class TelegramWebhookController extends Controller
{
    public function __construct(private readonly TelegramService $telegram)
    {}

    public function handle(Request $request, string $secret)
    {
        $expectedSecret = (string) config('services.telegram.webhook_secret', '');

        // Defense in depth: verify both the secret path segment and the
        // official X-Telegram-Bot-Api-Secret-Token header Telegram sends
        // when a secret_token was set via setWebhook.
        $headerSecret = $request->header('X-Telegram-Bot-Api-Secret-Token', '');

        if ($expectedSecret === '' || $secret !== $expectedSecret || $headerSecret !== $expectedSecret) {
            abort(Response::HTTP_NOT_FOUND);
        }

        $message = $request->input('message');

        if (!is_array($message)) {
            return response()->json(['ok' => true]);
        }

        $chatId = $message['chat']['id'] ?? null;
        $text   = trim((string) ($message['text'] ?? ''));

        if (!$chatId || $text === '') {
            return response()->json(['ok' => true]);
        }

        $this->tryLinkAccount((string) $chatId, $text);

        return response()->json(['ok' => true]);
    }

    private function tryLinkAccount(string $chatId, string $text): void
    {
        $token = null;

        if (str_starts_with($text, '/start')) {
            $parts = explode(' ', $text, 2);
            $token = trim($parts[1] ?? '');
        } elseif (!str_starts_with($text, '/')) {
            $token = $text;
        }

        if (!$token) {
            return;
        }

        $user = User::where('telegram_link_token', $token)->first();

        if (!$user) {
            $this->telegram->sendMessage($chatId, 'This linking code is invalid or has expired. Please generate a new one from your MeshBeacon profile settings.');

            return;
        }

        $user->forceFill([
            'telegram_chat_id'    => $chatId,
            'telegram_link_token' => null,
        ])->save();

        Log::info('TelegramWebhookController: linked account', ['user_id' => $user->id]);

        $this->telegram->sendMessage(
            $chatId,
            "\u{2705} This Telegram account is now linked to MeshBeacon responder <b>{$user->name}</b>. You will receive SOS alerts here."
        );
    }
}
