<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

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
        Inertia::share([
            'auth' => function () {
                $user = Auth::user();
                dd($user);
                return [
                    'user' => $user,
                    'roles' => $user ? $user->getRoleNames() : [],
                    'permissions' => $user
                        ? $user->getPermissionsViaRoles()->pluck('name')->merge(
                            $user->permissions->pluck('name')
                        )->unique()->values()
                        : [],
                ];
            },
        ]);
    }
}
