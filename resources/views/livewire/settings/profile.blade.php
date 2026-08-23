<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('Profile Settings') }}</flux:heading>

    <x-settings.layout>
        <div class="divide-y divide-white/10">
            <div class="grid grid-cols-1 gap-x-8 gap-y-8 py-10 first:pt-0 lg:grid-cols-3">
                <div class="px-4 sm:px-0">
                    <h2 class="text-base/7 font-semibold text-gray-900 dark:text-white">{{ __('Profile') }}</h2>
                    <p class="mt-1 text-sm/6 text-gray-500 dark:text-gray-400">{{ __('Your name and email are visible to other responders using this system.') }}</p>
                </div>

                <form wire:submit="updateProfileInformation" class="bg-white dark:bg-gray-800/50 outline outline-1 -outline-offset-1 outline-gray-200 dark:outline-white/10 sm:rounded-xl lg:col-span-2">
                    <div class="px-4 py-6 sm:p-8">
                        <div class="grid max-w-2xl grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-6">
                            <div class="sm:col-span-4">
                                <label for="name" class="block text-sm/6 font-medium text-gray-900 dark:text-white">{{ __('Name') }}</label>
                                <div class="mt-2">
                                    <input wire:model="name" id="name" type="text" required autofocus autocomplete="name"
                                        class="block w-full rounded-md bg-gray-100 dark:bg-white/5 px-3 py-1.5 text-base text-gray-900 dark:text-white outline outline-1 -outline-offset-1 outline-gray-200 dark:outline-white/10 placeholder:text-gray-500 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-500 sm:text-sm/6" />
                                </div>
                                @error('name')
                                    <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="sm:col-span-4">
                                <label for="email" class="block text-sm/6 font-medium text-gray-900 dark:text-white">{{ __('Email') }}</label>
                                <div class="mt-2">
                                    <input wire:model="email" id="email" type="email" required autocomplete="email"
                                        class="block w-full rounded-md bg-gray-100 dark:bg-white/5 px-3 py-1.5 text-base text-gray-900 dark:text-white outline outline-1 -outline-offset-1 outline-gray-200 dark:outline-white/10 placeholder:text-gray-500 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-500 sm:text-sm/6" />
                                </div>
                                @error('email')
                                    <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                                @enderror

                                @if ($this->hasUnverifiedEmail)
                                    <p class="mt-3 text-sm/6 text-gray-500 dark:text-gray-400">
                                        {{ __('Your email address is unverified.') }}

                                        <button type="button" wire:click.prevent="resendVerificationNotification" class="font-semibold text-indigo-400 hover:text-indigo-300">
                                            {{ __('Click here to re-send the verification email.') }}
                                        </button>
                                    </p>

                                    @if (session('status') === 'verification-link-sent')
                                        <p class="mt-2 text-sm/6 font-medium text-green-400">
                                            {{ __('A new verification link has been sent to your email address.') }}
                                        </p>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-x-6 border-t border-gray-200 dark:border-white/10 px-4 py-4 sm:px-8">
                        <x-action-message class="me-3 text-sm/6 text-gray-500 dark:text-gray-400" on="profile-updated">
                            {{ __('Saved.') }}
                        </x-action-message>

                        <button type="submit" class="rounded-md bg-indigo-500 px-3 py-2 text-sm font-semibold text-gray-900 dark:text-white hover:bg-indigo-400 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-500">
                            {{ __('Save') }}
                        </button>
                    </div>
                </form>
            </div>

            @if ($this->telegramConfigured)
                <div class="grid grid-cols-1 gap-x-8 gap-y-8 py-10 lg:grid-cols-3">
                    <div class="px-4 sm:px-0">
                        <h2 class="text-base/7 font-semibold text-gray-900 dark:text-white">{{ __('SOS Alerts via Telegram') }}</h2>
                        <p class="mt-1 text-sm/6 text-gray-500 dark:text-gray-400">{{ __('Link your Telegram account to receive instant SOS alert notifications - free, with no per-message cost.') }}</p>
                    </div>

                    <div class="bg-white dark:bg-gray-800/50 outline outline-1 -outline-offset-1 outline-gray-200 dark:outline-white/10 sm:rounded-xl lg:col-span-2">
                        <div class="px-4 py-6 sm:p-8">
                            @if ($telegramLinked)
                                <div class="flex items-center gap-3">
                                    <flux:badge color="green">{{ __('Linked') }}</flux:badge>
                                    <button type="button" wire:click="unlinkTelegram" class="text-sm/6 font-semibold text-gray-900 dark:text-white hover:text-gray-600 dark:text-gray-300">
                                        {{ __('Unlink') }}
                                    </button>
                                </div>
                            @else
                                <div class="space-y-3">
                                    <button type="button" wire:click="generateTelegramLinkToken"
                                        class="rounded-md bg-gray-200 dark:bg-white/10 px-3 py-2 text-sm font-semibold text-gray-900 dark:text-white ring-1 ring-inset ring-white/5 hover:bg-white/20">
                                        {{ __('Generate linking code') }}
                                    </button>

                                    @if ($telegramLinkToken)
                                        <p class="text-sm/6 text-gray-500 dark:text-gray-400">
                                            {{ __('Open Telegram and message') }}
                                            @if ($this->telegramBotUsername)
                                                <flux:link href="https://t.me/{{ $this->telegramBotUsername }}?start={{ $telegramLinkToken }}" target="_blank">
                                                    &commat;{{ $this->telegramBotUsername }}
                                                </flux:link>
                                            @endif
                                            {{ __('with this code:') }} <span class="font-mono font-semibold text-gray-900 dark:text-white">{{ $telegramLinkToken }}</span>
                                        </p>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif


            @if ($this->showDeleteUser)
                <livewire:settings.delete-user-form />
            @endif
        </div>
    </x-settings.layout>
</section>

