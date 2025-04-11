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
            'name' => 'Adminnim',
            'code' => 'admin',
            'description' => 'Administrator with full access',
        ]);

        Role::create([
            'name' => 'Userres',
            'code' => 'user',
            'description' => 'Regular user',
        ]);

        Role::create([
            'name' => 'Guesttse',
            'code' => 'guest',
            'description' => 'Guest user with limited access',
        ]);

    }
}
