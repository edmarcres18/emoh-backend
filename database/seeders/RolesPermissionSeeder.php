<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // User permissions
            'create user',
            'edit user',
            'delete user',
            'view user',
            
            // Role permissions
            'create role',
            'edit role',
            'delete role',
            'view role',
            
            // Permission permissions
            'create permission',
            'edit permission',
            'delete permission',
            'view permission',
            
            // Category permissions
            'create category',
            'edit category',
            'delete category',
            'view category',
            
            // Location permissions
            'create location',
            'edit location',
            'delete location',
            'view location',
            
            // Property permissions
            'create property',
            'edit property',
            'delete property',
            'view property',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions
        $systemAdminRole = Role::create(['name' => 'System Admin']);
        $adminRole = Role::create(['name' => 'Admin']);

        // Assign all permissions to System Admin
        $systemAdminRole->givePermissionTo(Permission::all());

        // Assign specific permissions to Admin
        $adminRole->givePermissionTo([
            'view user', 
            'view role', 
            'create role',
            'view permission',
            'create permission',
            'view category', 
            'create category', 
            'edit category', 
            'delete category',
            'view location', 
            'create location', 
            'edit location', 
            'delete location',
            'view property', 
            'create property', 
            'edit property', 
            'delete property'
        ]);
    }
}
