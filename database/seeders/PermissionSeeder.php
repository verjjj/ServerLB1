<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            ['name' => 'Get user list', 'code' => 'get-list-users'],
            ['name' => 'Read user', 'code' => 'read-user'],
            ['name' => 'Create user', 'code' => 'create-user'],
            ['name' => 'Update user', 'code' => 'update-user'],
            ['name' => 'Delete user', 'code' => 'delete-user'],
            ['name' => 'Get user story', 'code' => 'get-story-user'],
            ['name' => 'User restore', 'code' => 'restore-user'],

            ['name' => 'Get role list', 'code' => 'get-list-roles'],
            ['name' => 'Read role', 'code' => 'read-role'],
            ['name' => 'Create role', 'code' => 'create-role'],
            ['name' => 'Update role', 'code' => 'update-role'],
            ['name' => 'Delete role', 'code' => 'delete-role'],
            ['name' => 'Get role story', 'code' => 'get-story-roles'],

            ['name' => 'Get permission list', 'code' => 'get-list-permission'],
            ['name' => 'Read permission', 'code' => 'read-permission'],
            ['name' => 'Create permission', 'code' => 'create-permission'],
            ['name' => 'Update permission', 'code' => 'update-permission'],
            ['name' => 'Delete permission', 'code' => 'delete-permission'],
            ['name' => 'Get permission story', 'code' => 'get-story-permission'],

            ['name' => 'Restore entity state', 'code' => 'restore-entity-state'],

            ['name' => 'No permissions', 'code' => 'no-permissions'],

            ['name' => 'View logs', 'code' => 'view-logs'],
            ['name' => 'View logs-requests list', 'code' => 'view-logs-requests-list'],
            ['name' => 'View logs-requests details', 'code' => 'view-logs-requests-details'],

            ['name' => 'Administrator access', 'code' => 'admin-access'],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['code' => $permission['code']],
                $permission
            );
        }
    }
}
