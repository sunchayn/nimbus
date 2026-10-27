<?php

namespace Workbench\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'John Doe',
                'email' => 'john.doe@example.com',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'role' => 'admin',
                'status' => 'active',
                'metadata' => json_encode(['source' => 'admin_panel', 'last_login_ip' => '192.168.1.100']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Jane Smith',
                'email' => 'jane.smith@example.com',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'role' => 'user',
                'status' => 'active',
                'metadata' => json_encode(['source' => 'website', 'last_login_ip' => '192.168.1.101']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Mike Johnson',
                'email' => 'mike.johnson@example.com',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'role' => 'moderator',
                'status' => 'active',
                'metadata' => json_encode(['source' => 'invitation', 'last_login_ip' => '192.168.1.102']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Sarah Wilson',
                'email' => 'sarah.wilson@example.com',
                'email_verified_at' => null,
                'password' => Hash::make('password'),
                'role' => 'user',
                'status' => 'pending',
                'metadata' => json_encode(['source' => 'website', 'verification_sent' => true]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'David Brown',
                'email' => 'david.brown@example.com',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'role' => 'user',
                'status' => 'inactive',
                'metadata' => json_encode(['source' => 'mobile_app', 'last_login_ip' => '192.168.1.103']),
                'created_at' => now()->subDays(30),
                'updated_at' => now()->subDays(5),
            ],
            [
                'name' => 'Emily Davis',
                'email' => 'emily.davis@example.com',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'role' => 'user',
                'status' => 'active',
                'metadata' => json_encode(['source' => 'social_login', 'provider' => 'google']),
                'created_at' => now()->subDays(15),
                'updated_at' => now(),
            ],
            [
                'name' => 'Robert Miller',
                'email' => 'robert.miller@example.com',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'role' => 'user',
                'status' => 'active',
                'metadata' => json_encode(['source' => 'website', 'last_login_ip' => '192.168.1.104']),
                'created_at' => now()->subDays(7),
                'updated_at' => now(),
            ],
            [
                'name' => 'Lisa Garcia',
                'email' => 'lisa.garcia@example.com',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'role' => 'user',
                'status' => 'active',
                'metadata' => json_encode(['source' => 'mobile_app', 'last_login_ip' => '192.168.1.105']),
                'created_at' => now()->subDays(3),
                'updated_at' => now(),
            ],
            [
                'name' => 'James Rodriguez',
                'email' => 'james.rodriguez@example.com',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'role' => 'user',
                'status' => 'active',
                'metadata' => json_encode(['source' => 'website', 'last_login_ip' => '192.168.1.106']),
                'created_at' => now()->subDays(1),
                'updated_at' => now(),
            ],
            [
                'name' => 'Maria Martinez',
                'email' => 'maria.martinez@example.com',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'role' => 'user',
                'status' => 'active',
                'metadata' => json_encode(['source' => 'social_login', 'provider' => 'facebook']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('users')->insert($users);
    }
}
