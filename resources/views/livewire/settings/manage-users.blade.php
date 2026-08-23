<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('Manage Users') }}</flux:heading>

    <x-settings.layout>
        <div class="divide-y divide-white/10">
            <div class="grid grid-cols-1 gap-x-8 gap-y-8 py-10 first:pt-0 lg:grid-cols-3">
                <div class="px-4 sm:px-0">
                    <h2 class="text-base/7 font-semibold text-gray-900 dark:text-white">{{ __('Add a new user') }}</h2>
                    <p class="mt-1 text-sm/6 text-gray-500 dark:text-gray-400">{{ __('Create accounts and manage roles for this MeshBeacon instance') }}</p>
                </div>

                <form wire:submit="createUser" class="bg-white dark:bg-gray-800/50 outline outline-1 -outline-offset-1 outline-gray-200 dark:outline-white/10 sm:rounded-xl lg:col-span-2">
                    <div class="px-4 py-6 sm:p-8">
                        <div class="grid max-w-2xl grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-6">
                            <div class="sm:col-span-4">
                                <label for="name" class="block text-sm/6 font-medium text-gray-900 dark:text-white">{{ __('Name') }}</label>
                                <div class="mt-2">
                                    <input wire:model="name" id="name" type="text" autocomplete="name"
                                        class="block w-full rounded-md bg-gray-100 dark:bg-white/5 px-3 py-1.5 text-base text-gray-900 dark:text-white outline outline-1 -outline-offset-1 outline-gray-200 dark:outline-white/10 placeholder:text-gray-500 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-500 sm:text-sm/6" />
                                </div>
                                @error('name')
                                    <p class="mt-2 text-sm text-red-700 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="sm:col-span-4">
                                <label for="email" class="block text-sm/6 font-medium text-gray-900 dark:text-white">{{ __('Email') }}</label>
                                <div class="mt-2">
                                    <input wire:model="email" id="email" type="email" autocomplete="off"
                                        class="block w-full rounded-md bg-gray-100 dark:bg-white/5 px-3 py-1.5 text-base text-gray-900 dark:text-white outline outline-1 -outline-offset-1 outline-gray-200 dark:outline-white/10 placeholder:text-gray-500 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-500 sm:text-sm/6" />
                                </div>
                                @error('email')
                                    <p class="mt-2 text-sm text-red-700 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="sm:col-span-3">
                                <label for="password" class="block text-sm/6 font-medium text-gray-900 dark:text-white">{{ __('Password') }}</label>
                                <div class="mt-2">
                                    <input wire:model="password" id="password" type="password" autocomplete="new-password"
                                        class="block w-full rounded-md bg-gray-100 dark:bg-white/5 px-3 py-1.5 text-base text-gray-900 dark:text-white outline outline-1 -outline-offset-1 outline-gray-200 dark:outline-white/10 placeholder:text-gray-500 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-500 sm:text-sm/6" />
                                </div>
                                @error('password')
                                    <p class="mt-2 text-sm text-red-700 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="sm:col-span-3">
                                <label for="password_confirmation" class="block text-sm/6 font-medium text-gray-900 dark:text-white">{{ __('Confirm Password') }}</label>
                                <div class="mt-2">
                                    <input wire:model="password_confirmation" id="password_confirmation" type="password" autocomplete="new-password"
                                        class="block w-full rounded-md bg-gray-100 dark:bg-white/5 px-3 py-1.5 text-base text-gray-900 dark:text-white outline outline-1 -outline-offset-1 outline-gray-200 dark:outline-white/10 placeholder:text-gray-500 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-500 sm:text-sm/6" />
                                </div>
                            </div>

                            <div class="sm:col-span-3">
                                <label for="role" class="block text-sm/6 font-medium text-gray-900 dark:text-white">{{ __('Role') }}</label>
                                <div class="mt-2 grid grid-cols-1">
                                    <select wire:model="role" id="role"
                                        class="col-start-1 row-start-1 w-full appearance-none rounded-md bg-gray-100 dark:bg-white/5 py-1.5 pl-3 pr-8 text-base text-gray-900 dark:text-white outline outline-1 -outline-offset-1 outline-gray-200 dark:outline-white/10 *:bg-white dark:bg-gray-800 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-500 sm:text-sm/6">
                                        <option value="{{ \App\Models\User::ROLE_OPERATOR }}">{{ __('Operator') }}</option>
                                        <option value="{{ \App\Models\User::ROLE_ADMIN }}">{{ __('Admin') }}</option>
                                    </select>
                                    <svg viewBox="0 0 16 16" fill="currentColor" data-slot="icon" aria-hidden="true" class="pointer-events-none col-start-1 row-start-1 mr-2 size-5 self-center justify-self-end text-gray-500 dark:text-gray-400 sm:size-4">
                                        <path d="M4.22 6.22a.75.75 0 0 1 1.06 0L8 8.94l2.72-2.72a.75.75 0 1 1 1.06 1.06l-3.25 3.25a.75.75 0 0 1-1.06 0L4.22 7.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" fill-rule="evenodd" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-x-6 border-t border-gray-200 dark:border-white/10 px-4 py-4 sm:px-8">
                        <x-action-message class="me-3 text-sm/6 text-gray-500 dark:text-gray-400" on="user-created">
                            {{ __('User created.') }}
                        </x-action-message>

                        <button type="submit" class="rounded-md bg-indigo-500 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-400 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-500">
                            {{ __('Create user') }}
                        </button>
                    </div>
                </form>
            </div>

            <div class="grid grid-cols-1 gap-x-8 gap-y-8 py-10 lg:grid-cols-3">
                <div class="px-4 sm:px-0">
                    <h2 class="text-base/7 font-semibold text-gray-900 dark:text-white">{{ __('Existing users') }}</h2>
                </div>

                <div class="bg-white dark:bg-gray-800/50 outline outline-1 -outline-offset-1 outline-gray-200 dark:outline-white/10 sm:rounded-xl lg:col-span-2">
                    <div class="px-4 py-6 sm:p-8">
                        @error('role')
                            <p class="mb-4 text-sm text-red-700 dark:text-red-400">{{ $message }}</p>
                        @enderror

                        @error('delete')
                            <p class="mb-4 text-sm text-red-700 dark:text-red-400">{{ $message }}</p>
                        @enderror

                        <div class="divide-y divide-white/10">
                            @foreach ($this->users as $user)
                                <div class="flex flex-wrap items-center justify-between gap-3 py-4 first:pt-0 last:pb-0">
                                    <div>
                                        <div class="text-sm/6 font-medium text-gray-900 dark:text-white">{{ $user->name }}</div>
                                        <div class="text-sm/6 text-gray-500 dark:text-gray-400">{{ $user->email }}</div>
                                    </div>

                                    <div class="flex items-center gap-3">
                                        <div class="grid grid-cols-1">
                                            <select wire:change="updateRole({{ $user->id }}, $event.target.value)"
                                                class="col-start-1 row-start-1 w-36 appearance-none rounded-md bg-gray-100 dark:bg-white/5 py-1.5 pl-3 pr-8 text-base text-gray-900 dark:text-white outline outline-1 -outline-offset-1 outline-gray-200 dark:outline-white/10 *:bg-white dark:bg-gray-800 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-500 sm:text-sm/6">
                                                <option value="{{ \App\Models\User::ROLE_OPERATOR }}" @selected($user->role === \App\Models\User::ROLE_OPERATOR)>{{ __('Operator') }}</option>
                                                <option value="{{ \App\Models\User::ROLE_ADMIN }}" @selected($user->role === \App\Models\User::ROLE_ADMIN)>{{ __('Admin') }}</option>
                                            </select>
                                            <svg viewBox="0 0 16 16" fill="currentColor" data-slot="icon" aria-hidden="true" class="pointer-events-none col-start-1 row-start-1 mr-2 size-5 self-center justify-self-end text-gray-500 dark:text-gray-400 sm:size-4">
                                                <path d="M4.22 6.22a.75.75 0 0 1 1.06 0L8 8.94l2.72-2.72a.75.75 0 1 1 1.06 1.06l-3.25 3.25a.75.75 0 0 1-1.06 0L4.22 7.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" fill-rule="evenodd" />
                                            </svg>
                                        </div>

                                        @unless ($user->id === auth()->id())
                                            <button type="button" wire:click="deleteUser({{ $user->id }})" wire:confirm="{{ __('Delete this user account?') }}"
                                                class="rounded-md bg-red-500 px-2.5 py-1.5 text-sm font-semibold text-white hover:bg-red-400 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-500">
                                                {{ __('Delete') }}
                                            </button>
                                        @endunless
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </x-settings.layout>
</section>

