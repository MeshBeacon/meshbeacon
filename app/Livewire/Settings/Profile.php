<?php

namespace App\Livewire\Settings;

use App\Concerns\ProfileValidationRules;
use App\Services\TelegramService;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Profile extends Component
{
    use ProfileValidationRules;

    public string $name = '';

    public string $email = '';

    public bool $telegramLinked = false;

    public string $telegramLinkToken = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
        $this->telegramLinked = (bool) Auth::user()->telegram_chat_id;
        $this->telegramLinkToken = (string) (Auth::user()->telegram_link_token ?? '');
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate($this->profileRules($user->id));

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $this->dispatch('profile-updated', name: $user->name);
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }

    /**
     * Generate a one-time code the user pastes/sends to the Telegram bot to
     * prove ownership of their chat and link it to this account.
     */
    public function generateTelegramLinkToken(): void
    {
        $user  = Auth::user();
        $token = Str::upper(Str::random(8));

        $user->forceFill(['telegram_link_token' => $token])->save();

        $this->telegramLinkToken = $token;
    }

    /**
     * Unlink the currently-connected Telegram chat from this account.
     */
    public function unlinkTelegram(): void
    {
        Auth::user()->forceFill([
            'telegram_chat_id'    => null,
            'telegram_link_token' => null,
        ])->save();

        $this->telegramLinked = false;
        $this->telegramLinkToken = '';
    }

    #[Computed]
    public function telegramBotUsername(): string
    {
        return config('services.telegram.bot_username', '');
    }

    #[Computed]
    public function telegramConfigured(): bool
    {
        return app(TelegramService::class)->isConfigured();
    }

    #[Computed]
    public function hasUnverifiedEmail(): bool
    {
        return Auth::user() instanceof MustVerifyEmail && ! Auth::user()->hasVerifiedEmail();
    }

    #[Computed]
    public function showDeleteUser(): bool
    {
        return ! Auth::user() instanceof MustVerifyEmail
            || (Auth::user() instanceof MustVerifyEmail && Auth::user()->hasVerifiedEmail());
    }
}
