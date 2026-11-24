<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use App\Support\Registration;
use App\Support\Workspaces;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

// the roles (admin, and config/auth.php registration.default_role), one admin to sign in with
// (admin@admin.com / password), and some accounts with the default role so the users list has something to show;
// drop those for production
class UserSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Role::insert([
            [
                'name' => 'admin',
                'description' => 'Full access, including user management',
            ],
            [
                'name' => Registration::defaultRole(),
                'description' => 'Signs in and uses the app; the role self-registered accounts get',
            ],
        ]);

        // the admin owns the workspace (self-hosted: the one everyone joins)
        Workspaces::provision(User::factory()->admin()->create([
            'name' => 'Admin User',
            'email' => 'admin@admin.com',
        ]));

        // joined over the last few weeks, a few still unconfirmed
        foreach (range(1, 24) as $i) {
            $joined = now()->subDays(random_int(0, 40))->subMinutes(random_int(0, 1440));

            Workspaces::provision(User::factory()->role(Registration::defaultRole())->create([
                'created_at' => $joined,
                'email_verified_at' => $i % 6 === 0 ? null : $joined->copy()->addMinutes(random_int(5, 600))->min(now()),
            ]));
        }
    }
}
