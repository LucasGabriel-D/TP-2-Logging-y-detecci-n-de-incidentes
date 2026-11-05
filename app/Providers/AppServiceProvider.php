<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Solo estos usuarios pueden ver el panel (didáctico: en un sistema real, un rol)
        Gate::define('viewSecurityDashboard', function (User $user) {
            return in_array($user->email, ['admin@test.com']);
        });
    }
}