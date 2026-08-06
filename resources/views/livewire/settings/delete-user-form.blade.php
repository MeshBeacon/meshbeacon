<div class="grid grid-cols-1 gap-x-8 gap-y-8 py-10 lg:grid-cols-3">
    <div class="px-4 sm:px-0">
        <h2 class="text-base/7 font-semibold text-white">{{ __('Delete account') }}</h2>
        <p class="mt-1 text-sm/6 text-gray-400">{{ __('Delete your account and all of its resources') }}</p>
    </div>

    <div class="bg-gray-800/50 outline outline-1 -outline-offset-1 outline-white/10 sm:rounded-xl lg:col-span-2">
        <div class="px-4 py-6 sm:p-8">
            <flux:modal.trigger name="confirm-user-deletion">
                <button type="button" x-data="" x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
                    class="rounded-md bg-red-500 px-3 py-2 text-sm font-semibold text-white hover:bg-red-400 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-500">
                    {{ __('Delete account') }}
                </button>
            </flux:modal.trigger>
        </div>
    </div>

    <flux:modal name="confirm-user-deletion" :show="$errors->isNotEmpty()" focusable class="max-w-lg">
        <form method="POST" wire:submit="deleteUser" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Are you sure you want to delete your account?') }}</flux:heading>

                <flux:subheading>
                    {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}
                </flux:subheading>
            </div>

            <flux:input wire:model="password" :label="__('Password')" type="password" />

            <div class="flex justify-end space-x-2 rtl:space-x-reverse">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="danger" type="submit">{{ __('Delete account') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>

