<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ChangeLog;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class ChangeLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $guestUser = User::create([
            'username' => 'TestUser',
            'email' => 'testtest@example.com',
            'password' => Hash::make('Password123!!'),
            'birthday' => '2002-10-10'
        ]);

        $adminUser = User::create([
            'username' => 'Adminnim',
            'email' => 'adminnim@example.com',
            'password' => Hash::make('Password123!!'),
            'birthday' => '2002-10-10'
        ]);

        $logs = [
            // Логи для TestUser
            [
                //Создаем
                'entity_type' => User::class,
                'entity_id' => $guestUser->id,
                'before' => null,
                'after' => json_encode([
                    'username' => 'TestUser',
                    'email' => 'testtest@example.com',
                    'password' => Hash::make('Password123!!'),
                    'birthday' => '2002-10-10'
                ]),
                'action' => 'create',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                //Изменяем имя
                'entity_type' => User::class,
                'entity_id' => $guestUser->id,
                'before' => json_encode([
                    'username' => 'TestUser',
                    'email' => 'testtest@example.com',
                    'password' => Hash::make('Password123!!'),
                    'birthday' => '2002-10-10'
                ]),
                'after' => json_encode([
                    'username' => 'UpdatedTestUser',
                    'email' => 'testtest@example.com',
                    'password' => Hash::make('Password123!!'),
                    'birthday' => '2002-10-10'
                ]),
                'action' => 'update',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                //Удаляем
                'entity_type' => User::class,
                'entity_id' => $guestUser->id,
                'before' => json_encode([
                    'username' => 'UpdatedTestUser',
                    'email' => 'testtest@example.com',
                    'password' => Hash::make('Password123!!'),
                    'birthday' => '2002-10-10'
                ]),
                'after' => json_encode([
                    'deleted_at' => now()->toDateTimeString()
                ]),
                'action' => 'delete',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                //Восстановление
                'entity_type' => User::class,
                'entity_id' => $guestUser->id,
                'before' => json_encode([
                    'deleted_at' => now()->toDateTimeString()
                ]),
                'after' => json_encode([
                    'deleted_at' => null
                ]),
                'action' => 'restore',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Логи для Adminnim
            [
                'entity_type' => User::class,
                'entity_id' => $adminUser->id,
                'before' => null,
                'after' => json_encode([
                    'username' => 'Adminnim',
                    'email' => 'adminnim@example.com',
                    'password' => Hash::make('Password123!!'),
                    'birthday' => '2002-10-10'
                ]),
                'action' => 'create',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'entity_type' => User::class,
                'entity_id' => $adminUser->id,
                'before' => json_encode([
                    'username' => 'Adminnim',
                    'email' => 'adminnim@example.com',
                    'password' => Hash::make('Password123!!'),
                    'birthday' => '2002-10-10'
                ]),
                'after' => json_encode([
                    'username' => 'UpdatedAdminnim',
                    'email' => 'adminnim@example.com',
                    'password' => Hash::make('Password123!!'),
                    'birthday' => '2002-10-10'
                ]),
                'action' => 'update',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'entity_type' => User::class,
                'entity_id' => $adminUser->id,
                'before' => json_encode([
                    'username' => 'UpdatedAdminnim',
                    'email' => 'adminnim@example.com',
                    'password' => Hash::make('Password123!!'),
                    'birthday' => '2002-10-10'
                ]),
                'after' => json_encode([
                    'deleted_at' => now()->toDateTimeString()
                ]),
                'action' => 'delete',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'entity_type' => User::class,
                'entity_id' => $adminUser->id,
                'before' => json_encode([
                    'deleted_at' => now()->toDateTimeString()
                ]),
                'after' => json_encode([
                    'deleted_at' => null
                ]),
                'action' => 'restore',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($logs as $log) {
            ChangeLog::create($log);
        }
    }
}
