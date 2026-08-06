<?php

namespace App\Livewire\Settings;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ManageUsers extends Component
{
    use PasswordValidationRules, ProfileValidationRules;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $role = User::ROLE_OPERATOR;

    /**
     * Mount the component, denying access to non-admins.
     */
    public function mount(): void
    {
        Gate::authorize('admin');
    }

    #[Computed]
    public function users()
    {
        return User::query()->orderBy('name')->get();
    }

    /**
     * Create a new user account.
     */
    public function createUser(): void
    {
        Gate::authorize('admin');

        $validated = $this->validate([
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
            'role' => ['required', Rule::in([User::ROLE_ADMIN, User::ROLE_OPERATOR])],
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => $validated['role'],
        ]);

        $this->reset(['name', 'email', 'password', 'password_confirmation']);
        $this->role = User::ROLE_OPERATOR;

        unset($this->users);

        $this->dispatch('user-created');
    }

    /**
     * Change an existing user's role, refusing to demote the last admin.
     */
    public function updateRole(int $userId, string $role): void
    {
        Gate::authorize('admin');

        if (! in_array($role, [User::ROLE_ADMIN, User::ROLE_OPERATOR], true)) {
            return;
        }

        $user = User::findOrFail($userId);

        if ($user->role === User::ROLE_ADMIN
            && $role !== User::ROLE_ADMIN
            && User::where('role', User::ROLE_ADMIN)->count() <= 1) {
            $this->addError('role', __('At least one admin account must remain.'));

            return;
        }

        $user->update(['role' => $role]);

        unset($this->users);
    }

    /**
     * Delete a user account, refusing to delete the last admin or yourself.
     */
    public function deleteUser(int $userId): void
    {
        Gate::authorize('admin');

        $user = User::findOrFail($userId);

        if ($user->id === Auth::id()) {
            $this->addError('delete', __('You cannot delete your own account.'));

            return;
        }

        if ($user->role === User::ROLE_ADMIN && User::where('role', User::ROLE_ADMIN)->count() <= 1) {
            $this->addError('delete', __('At least one admin account must remain.'));

            return;
        }

        $user->delete();

        unset($this->users);
    }

    public function render()
    {
        return view('livewire.settings.manage-users');
    }
}
