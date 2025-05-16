<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User; 

class RolesAndPermissionsSeeder extends Seeder
{
    public function run()
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'use-pdf-tools',
            'view-own-usage-history',
            'view-any-usage-history',
            'export-any-usage-history',
            'delete-any-usage-history',
            'view-users',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $userRole = Role::firstOrCreate(['name' => 'user']);
        $userRole->givePermissionTo([
            'use-pdf-tools',
            'view-own-usage-history',
        ]);

        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->givePermissionTo(Permission::all()); 

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
    }
}