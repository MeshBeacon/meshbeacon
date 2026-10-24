<?php

namespace App\Livewire\Settings;

use App\Concerns\PasswordValidationRules;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Component;

class ResetDatabase extends Component
{
    use PasswordValidationRules;

    /**
     * The exact phrase an administrator must type to confirm the reset.
     */
    protected const CONFIRMATION_PHRASE = 'RESET DATABASE';

    public string $password = '';

    public string $confirmationPhrase = '';

    /**
     * When true, the currently signed-in administrator's name, email, and
     * password are preserved across the wipe instead of re-seeding the
     * default MESHBEACON_ADMIN_EMAIL / MESHBEACON_ADMIN_PASSWORD account.
     */
    public bool $preserveAdminCredentials = true;

    public function mount(): void
    {
        Gate::authorize('admin');
    }

    /**
     * Whether the active database connection is SQLite, the only driver a
     * raw file snapshot can be taken of.
     */
    public function getIsSqliteProperty(): bool
    {
        return DB::connection()->getDriverName() === 'sqlite';
    }

    /**
     * Stream a consistent point-in-time copy of the raw SQLite database file
     * for download, for off-site/offline backup during disaster recovery.
     *
     * Uses SQLite's own `VACUUM INTO` so the snapshot is taken atomically
     * even while the application is live and writing to the database.
     */
    public function downloadSnapshot(): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        Gate::authorize('admin');

        if (! $this->isSqlite) {
            $this->addError('confirmationPhrase', __('A raw database snapshot is only available when using SQLite.'));

            abort(409);
        }

        Log::warning('Database snapshot downloaded', [
            'user_id' => Auth::id(),
            'email' => Auth::user()->email,
            'ip' => request()->ip(),
        ]);

        $filename = 'meshbeacon-snapshot_'.now()->format('Y-m-d_His').'.sqlite';

        $directory = storage_path('app/private/snapshots');

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $snapshotPath = $directory.DIRECTORY_SEPARATOR.$filename;

        DB::statement('VACUUM INTO ?', [$snapshotPath]);

        return response()->download($snapshotPath, $filename)->deleteFileAfterSend();
    }

    /**
     * Wipe the entire database and re-seed a fresh administrator account.
     *
     * Intended for on-site emergency/disaster recovery scenarios where the
     * fastest path forward is to return the instance to a clean install
     * state rather than attempt to repair corrupted data.
     */
    public function resetDatabase(): void
    {
        Gate::authorize('admin');

        $this->validate([
            'password' => $this->currentPasswordRules(),
        ]);

        if ($this->confirmationPhrase !== self::CONFIRMATION_PHRASE) {
            $this->addError('confirmationPhrase', __('Type ":phrase" exactly to confirm.', [
                'phrase' => self::CONFIRMATION_PHRASE,
            ]));

            return;
        }

        $preservedAdmin = null;

        if ($this->preserveAdminCredentials) {
            $currentAdmin = Auth::user();

            $preservedAdmin = [
                'name' => $currentAdmin->name,
                'email' => $currentAdmin->email,
                'password' => $currentAdmin->password,
                'role' => User::ROLE_ADMIN,
                'locale' => $currentAdmin->locale,
                'email_verified_at' => $currentAdmin->email_verified_at,
            ];
        } elseif (trim((string) env('MESHBEACON_ADMIN_PASSWORD', '')) === '') {
            // Without a preserved admin, the seeder that recreates the
            // administrator account after the wipe requires these to be
            // set, otherwise the instance would be left with no way to log
            // back in.
            $this->addError('confirmationPhrase', __(
                'MESHBEACON_ADMIN_PASSWORD is not configured in the environment. '.
                'Set it before resetting the database, otherwise you will be locked out.'
            ));

            return;
        }

        Log::warning('Database reset requested', [
            'user_id' => Auth::id(),
            'email' => Auth::user()->email,
            'ip' => request()->ip(),
            'preserve_admin_credentials' => $this->preserveAdminCredentials,
        ]);

        // This destructive command is normally prohibited in production
        // (see AppServiceProvider::configureDefaults()). Temporarily lift
        // that guard for this explicit, password- and phrase-confirmed
        // admin action, then restore it immediately afterward.
        DB::prohibitDestructiveCommands(false);

        try {
            $exitCode = Artisan::call('migrate:fresh', [
                '--force' => true,
                '--seed' => ! $preservedAdmin,
            ]);

            if ($exitCode !== 0) {
                throw new \RuntimeException(Artisan::output());
            }

            if ($preservedAdmin) {
                (new User)->forceFill($preservedAdmin)->save();
            }
        } catch (\Throwable $e) {
            Log::error('Database reset failed', ['error' => $e->getMessage()]);

            $this->addError('confirmationPhrase', __('Database reset failed: :error', [
                'error' => Str::limit($e->getMessage(), 300),
            ]));

            return;
        } finally {
            DB::prohibitDestructiveCommands(app()->isProduction());
        }

        $admin = User::query()->where('role', User::ROLE_ADMIN)->first();

        if ($admin) {
            Auth::login($admin);
            request()->session()->regenerate();
        }

        Log::warning('Database reset completed', ['new_admin_email' => $admin?->email]);

        session()->flash('status', __('The database has been reset to a fresh install state.'));

        $this->redirect('/dashboard', navigate: true);
    }

    public function render()
    {
        return view('livewire.settings.reset-database');
    }
}
