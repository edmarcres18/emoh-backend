<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create System Administrator user
        $systemAdmin = User::create([
            'name' => 'System Administrator',
            'email' => 'chuweywebdev@gmail.com',
            'password' => Hash::make('Chuwey#081021'),
            'email_verified_at' => now(),
        ]);

        // Assign System Admin role
        $systemAdmin->assignRole('System Admin');

        // Create Admin user
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        // Assign Admin role
        $admin->assignRole('Admin');
    }
}
