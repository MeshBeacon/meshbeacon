<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // Since public self-registration is disabled, a fresh install needs
        // at least one admin account. Re-running the seeder is safe once an
        // administrator already exists.
        if (! User::query()->where('role', User::ROLE_ADMIN)->exists()) {
            $adminPassword = (string) env('MESHBEACON_ADMIN_PASSWORD', '');

            if ($adminPassword === '') {
                throw new \RuntimeException(
                    'Set MESHBEACON_ADMIN_PASSWORD before seeding the database.'
                );
            }

            $admin = new User([
                'name' => 'Admin',
                'email' => env('MESHBEACON_ADMIN_EMAIL', 'admin@example.com'),
                'password' => $adminPassword,
                'role' => User::ROLE_ADMIN,
            ]);

            $admin->email_verified_at = now();
            $admin->save();
        }
    }
}
