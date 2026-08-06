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
        // at least one admin account seeded so someone can sign in and
        // provision further users via Settings > Manage Users. Guarded so
        // re-running the seeder (e.g. on every container start) is safe and
        // won't error out once an admin already exists.
        if (! User::query()->where('role', User::ROLE_ADMIN)->exists()) {
            User::factory()->create([
                'name' => 'Admin',
                'email' => 'admin@example.com',
                'role' => User::ROLE_ADMIN,
            ]);
        }
    }
}
