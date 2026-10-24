<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('Reset Database') }}</flux:heading>

    <x-settings.layout>
        <div class="grid grid-cols-1 gap-x-8 gap-y-8 py-10 first:pt-0 lg:grid-cols-3">
            <div class="px-4 sm:px-0">
                <h2 class="text-base/7 font-semibold text-gray-900 dark:text-white">{{ __('Backups') }}</h2>
                <p class="mt-1 text-sm/6 text-gray-500 dark:text-gray-400">
                    {{ __('Download a raw, point-in-time copy of the database file for off-site or offline storage.') }}
                </p>
            </div>

            <div class="bg-white dark:bg-gray-800/50 outline outline-1 -outline-offset-1 outline-gray-200 dark:outline-white/10 sm:rounded-xl lg:col-span-2">
                <div class="px-4 py-6 sm:p-8 space-y-4">
                    @if ($this->isSqlite)
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            {{ __('The snapshot is a complete, consistent copy of the live SQLite database file, taken safely even while the application is running.') }}
                        </p>

                        <flux:button wire:click="downloadSnapshot" wire:target="downloadSnapshot">
                            <span wire:loading.remove wire:target="downloadSnapshot">{{ __('Download database snapshot') }}</span>
                            <span wire:loading wire:target="downloadSnapshot">{{ __('Preparing snapshot…') }}</span>
                        </flux:button>
                    @else
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            {{ __('A raw database snapshot is only available when the application is using SQLite.') }}
                        </p>
                    @endif
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-x-8 gap-y-8 py-10 first:pt-0 lg:grid-cols-3">
            <div class="px-4 sm:px-0">
                <h2 class="text-base/7 font-semibold text-red-700 dark:text-red-400">{{ __('Danger zone') }}</h2>
                <p class="mt-1 text-sm/6 text-gray-500 dark:text-gray-400">
                    {{ __('Wipe every table and return this instance to a fresh install state. Intended for on-site emergency/disaster recovery only.') }}
                </p>
            </div>

            <div class="bg-white dark:bg-gray-800/50 outline outline-1 -outline-offset-1 outline-red-200 dark:outline-red-500/20 sm:rounded-xl lg:col-span-2">
                <div class="px-4 py-6 sm:p-8 space-y-4">
                    <ul class="list-disc list-inside text-sm text-gray-600 dark:text-gray-400 space-y-1">
                        <li>{{ __('All ducks, incidents, logs, and settings will be permanently deleted. All other user accounts will be removed.') }}</li>
                        <li>{{ __('Sessions, cache, and queued jobs are cleared, so every signed-in user (including you) will be logged out of their current session.') }}</li>
                        <li>{{ __('By default your own name, email, and password are kept so you can log back in immediately. Untick "Keep my current login" below to instead re-seed the default admin account from the MESHBEACON_ADMIN_EMAIL / MESHBEACON_ADMIN_PASSWORD environment variables.') }}</li>
                        <li>{{ __('Restart background workers (queue, MQTT subscriber, scheduler) afterward so they pick up a clean state.') }}</li>
                    </ul>

                    <flux:modal.trigger name="confirm-database-reset">
                        <flux:button variant="danger">{{ __('Reset database') }}</flux:button>
                    </flux:modal.trigger>
                </div>
            </div>
        </div>

        <flux:modal name="confirm-database-reset" :show="$errors->isNotEmpty()" focusable class="max-w-lg">
            <form method="POST" wire:submit="resetDatabase" class="space-y-6">
                <div>
                    <flux:heading size="lg">{{ __('Reset the entire database?') }}</flux:heading>

                    <flux:subheading>
                        {{ __('This permanently deletes all application data and cannot be undone. Enter your password and type the confirmation phrase below to proceed.') }}
                    </flux:subheading>
                </div>

                <flux:input wire:model="password" :label="__('Password')" type="password" />

                <flux:input
                    wire:model="confirmationPhrase"
                    :label="__('Type \'RESET DATABASE\' to confirm')"
                    type="text"
                    autocomplete="off"
                />

                <flux:checkbox
                    wire:model="preserveAdminCredentials"
                    :label="__('Keep my current login')"
                    :description="__('Preserve your name, email, and password across the wipe instead of re-seeding the default admin account from the server environment.')"
                />

                <div class="flex justify-end space-x-2 rtl:space-x-reverse">
                    <flux:modal.close>
                        <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
                    </flux:modal.close>

                    <flux:button variant="danger" type="submit">{{ __('Reset database') }}</flux:button>
                </div>
            </form>
        </flux:modal>
    </x-settings.layout>
</section>
