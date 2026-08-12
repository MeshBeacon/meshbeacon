<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('Appearance Settings') }}</flux:heading>

    <x-settings.layout>
        <div class="grid grid-cols-1 gap-x-8 gap-y-8 py-10 first:pt-0 md:grid-cols-3">
            <div class="px-4 sm:px-0">
                <h2 class="text-base/7 font-semibold text-gray-900 dark:text-white">{{ __('Appearance') }}</h2>
                <p class="mt-1 text-sm/6 text-gray-500 dark:text-gray-400">{{ __('Update the appearance settings for your account') }}</p>
            </div>

            <div class="bg-white dark:bg-gray-800/50 outline outline-1 -outline-offset-1 outline-gray-200 dark:outline-white/10 sm:rounded-xl md:col-span-2">
                <div class="px-4 py-6 sm:p-8">
                    <flux:radio.group x-data variant="segmented" x-model="$flux.appearance">
                        <flux:radio value="light" icon="sun">{{ __('Light') }}</flux:radio>
                        <flux:radio value="dark" icon="moon">{{ __('Dark') }}</flux:radio>
                        <flux:radio value="system" icon="computer-desktop">{{ __('System') }}</flux:radio>
                    </flux:radio.group>
                </div>
            </div>
        </div>
    </x-settings.layout>
</section>

