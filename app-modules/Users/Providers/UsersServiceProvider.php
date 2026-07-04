<?php

declare(strict_types=1);

namespace Modules\Users\Providers;

use App\Models\User;
use App\Modules\ModuleServiceProvider;
use App\Modules\NavRegistry;
use App\Modules\PermissionRegistry;
use Illuminate\Support\Facades\Gate;
use Modules\Users\Policies\UserPolicy;

final class UsersServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
        Gate::policy(User::class, UserPolicy::class);
    }

    protected function name(): string
    {
        return 'users';
    }

    protected function navigation(NavRegistry $nav): void
    {
        $nav->add(
            module: $this->name(),
            label: 'Users',
            routeName: 'admin.users.index',
            icon: 'users',
            permission: 'users.view',
            group: 'Management',
            sort: 10,
        );
    }

    protected function permissions(PermissionRegistry $permissions): void
    {
        $permissions->declare('users.view', roles: ['admin']);
        $permissions->declare('users.create', roles: ['admin']);
        $permissions->declare('users.update', roles: ['admin']);
        $permissions->declare('users.delete', roles: ['admin']);
        $permissions->declare('users.export', roles: ['admin']);
    }
}
