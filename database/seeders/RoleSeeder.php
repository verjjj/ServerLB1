<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'Admin',
                'code' => 'admin',
                'description' => 'Administrator with full access',
            ],
            [
                'name' => 'User',
                'code' => 'user',
                'description' => 'Regular user',
            ],
            [
                'name' => 'Guest',
                'code' => 'guest',
                'description' => 'Guest user with limited access',
            ],
        ];

        foreach ($roles as $roleData) {
            Role::firstOrCreate(
                ['code' => $roleData['code']],
                $roleData
            );
        }
    }
}
