<?php

namespace App\Database\Seeds;

use Google\Service\Dfareporting\Resource\UserRoles;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed your application's database.
     * @return void
     */
    public function run(): array
    {
        return [
            UsersSeeder::class,
            ReviewTypesSeeder::class,
            TemplatesSeeder::class,
            UserRolesSeeder::class,
        ];
    }
}
