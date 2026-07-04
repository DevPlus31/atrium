<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use App\Modules\NavRegistry;
use App\Modules\PermissionRegistry;
use App\Modules\WidgetRegistry;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(NavRegistry::class);
        $this->app->singleton(PermissionRegistry::class);
        $this->app->singleton(WidgetRegistry::class);
    }

    public function boot(): void
    {
        Gate::before(static fn (User $user): ?bool => $user->hasRole('super-admin') ? true : null);
    }
}
