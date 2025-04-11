<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        //сюда код не попадает, исправить
        Role::create([
            'name' => 'Admin',
            'code' => 'admin',
            'description' => 'Administrator with full access',
        ]);

        Role::create([
            'name' => 'User',
            'code' => 'user',
            'description' => 'Regular user',
        ]);

        Role::create([
            'name' => 'Guest',
            'code' => 'guest',
            'description' => 'Guest user with limited access',
        ]);

    }
}
