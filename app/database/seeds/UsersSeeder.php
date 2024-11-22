<?php
namespace App\Database\Seeds;

use App\Models\User;
use Leaf\Helpers\Password;

use Illuminate\Database\Seeder;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * @return void
     */
    public function run()
    {        
        User::create([
            'username' => 'admin',
            'fullname' => 'Admin User',
            'email' => 'admin@admin.com',
            'password' => Password::hash('password'),
            'role' => 'admin',
            'avatar' => '/assets/images/users/avatar.jpg',
            'email_verified' => 1
        ]);
    }
}
