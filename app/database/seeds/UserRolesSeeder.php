<?php
namespace App\Database\Seeds;

use App\Models\UserRole;
use Illuminate\Database\Seeder;

class UserRolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * @return void
     */
    public function run()
    {
        UserRole::create([
            'name' => 'admin',
            'description' => 'Administrator',
            'is_admin' => true
        ]);

        UserRole::create([
            'name' => 'moderator',
            'description' => 'Moderator',
            'is_admin' => false
        ]);

        UserRole::create([
            'name' => 'user',
            'description' => 'User',
            'is_admin' => false
        ]);
    }
}
