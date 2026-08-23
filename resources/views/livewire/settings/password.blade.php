<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('Password Settings') }}</flux:heading>

    <x-settings.layout>
        <div class="grid grid-cols-1 gap-x-8 gap-y-8 py-10 first:pt-0 lg:grid-cols-3">
            <div class="px-4 sm:px-0">
                <h2 class="text-base/7 font-semibold text-gray-900 dark:text-white">{{ __('Update password') }}</h2>
                <p class="mt-1 text-sm/6 text-gray-500 dark:text-gray-400">{{ __('Ensure your account is using a long, random password to stay secure') }}</p>
            </div>

            <form method="POST" wire:submit="updatePassword" class="bg-white dark:bg-gray-800/50 outline outline-1 -outline-offset-1 outline-gray-200 dark:outline-white/10 sm:rounded-xl lg:col-span-2">
                <div class="px-4 py-6 sm:p-8">
                    <div class="grid max-w-2xl grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-6">
                        <div class="col-span-full">
                            <label for="current_password" class="block text-sm/6 font-medium text-gray-900 dark:text-white">{{ __('Current password') }}</label>
                            <div class="mt-2">
                                <input wire:model="current_password" id="current_password" type="password" required autocomplete="current-password"
                                    class="block w-full rounded-md bg-gray-100 dark:bg-white/5 px-3 py-1.5 text-base text-gray-900 dark:text-white outline outline-1 -outline-offset-1 outline-gray-200 dark:outline-white/10 placeholder:text-gray-500 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-500 sm:text-sm/6" />
                            </div>
                            @error('current_password')
                                <p class="mt-2 text-sm text-red-700 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="col-span-full">
                            <label for="password" class="block text-sm/6 font-medium text-gray-900 dark:text-white">{{ __('New password') }}</label>
                            <div class="mt-2">
                                <input wire:model="password" id="password" type="password" required autocomplete="new-password"
                                    class="block w-full rounded-md bg-gray-100 dark:bg-white/5 px-3 py-1.5 text-base text-gray-900 dark:text-white outline outline-1 -outline-offset-1 outline-gray-200 dark:outline-white/10 placeholder:text-gray-500 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-500 sm:text-sm/6" />
                            </div>
                            @error('password')
                                <p class="mt-2 text-sm text-red-700 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="col-span-full">
                            <label for="password_confirmation" class="block text-sm/6 font-medium text-gray-900 dark:text-white">{{ __('Confirm Password') }}</label>
                            <div class="mt-2">
                                <input wire:model="password_confirmation" id="password_confirmation" type="password" required autocomplete="new-password"
                                    class="block w-full rounded-md bg-gray-100 dark:bg-white/5 px-3 py-1.5 text-base text-gray-900 dark:text-white outline outline-1 -outline-offset-1 outline-gray-200 dark:outline-white/10 placeholder:text-gray-500 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-500 sm:text-sm/6" />
                            </div>
                            @error('password_confirmation')
                                <p class="mt-2 text-sm text-red-700 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-x-6 border-t border-gray-200 dark:border-white/10 px-4 py-4 sm:px-8">
                    <x-action-message class="me-3 text-sm/6 text-gray-500 dark:text-gray-400" on="password-updated">
                        {{ __('Saved.') }}
                    </x-action-message>

                    <button type="submit" class="rounded-md bg-indigo-500 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-400 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-500">
                        {{ __('Save') }}
                    </button>
                </div>
            </form>
        </div>
    </x-settings.layout>
</section>

