<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (Schema::hasTable('permissions') && Schema::hasTable('roles')) {
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

            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

            Inertia::share([
                'auth' => fn () => [
                    'user' => Auth::user(),
                ],
                'canUsePdfTools' => fn () =>
                    Auth::check() && Auth::user()->can('use-pdf-tools'),

                'canViewUsers' => fn () =>
                    Auth::check() && Auth::user()->can('view-users'),

                'canViewOwnHistory' => fn () =>
                    Auth::check() && Auth::user()->can('view-own-usage-history'),

                'canViewAnyHistory' => fn () =>
                    Auth::check() && Auth::user()->can('view-any-usage-history'),

                'canExportHistory' => fn () =>
                    Auth::check() && Auth::user()->can('export-any-usage-history'),

                'canDeleteHistory' => fn () =>
                    Auth::check() && Auth::user()->can('delete-any-usage-history'),

                'isAdmin' => fn () =>
                    Auth::check() && Auth::user()->hasRole('admin'),
            ]);
        }
    }
}
