<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User; 
use App\Services\ApiKeyService;

class RolesAndPermissionsSeeder extends Seeder
{
public function run()
    {
        // Define all permissions
        $permissions = [
            'use-pdf-tools',
            'view-own-usage-history',
            'view-any-usage-history',
            'export-any-usage-history',
            'delete-any-usage-history',
            'view-users',
        ];

        // Create permissions
        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name]);
        }

        // Create roles
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $userRole = Role::firstOrCreate(['name' => 'user']);

        // Assign permissions to roles
        $adminRole->syncPermissions($permissions);
        $userRole->syncPermissions([
            'use-pdf-tools',
            'view-own-usage-history',
        ]);

        // Create users
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'first_name' => 'Admin',
                'last_name' => 'User',
                'username' => 'admin',
                'password' => bcrypt('password'),
            ]
        );
        $adminUser->assignRole($adminRole);
        app(ApiKeyService::class)->generate($adminUser);
        $regularUser = User::firstOrCreate(
            ['email' => 'user@example.com'],
            [
                'first_name' => 'Regular',
                'last_name' => 'User',
                'username' => 'regular',
                'password' => bcrypt('password'),
            ]
        );
        $regularUser->assignRole($userRole);
        app(ApiKeyService::class)->generate($regularUser);
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
}