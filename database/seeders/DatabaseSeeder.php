<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Run roles and permissions seeder first
        $this->call([
            RolesPermissionSeeder::class,
            AdminUserSeeder::class,
            SiteSettingsSeeder::class,
            CategorySeeder::class,
            LocationsSeeder::class,
        ]);

    }
}
