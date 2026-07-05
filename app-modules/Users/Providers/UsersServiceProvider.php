<?php

declare(strict_types=1);

namespace Modules\Users\Providers;

use App\Models\User;
use App\Modules\ModuleServiceProvider;
use App\Modules\NavRegistry;
use App\Modules\PermissionRegistry;
use App\Modules\WidgetRegistry;
use Illuminate\Support\Facades\Gate;
use Modules\Users\Policies\UserPolicy;
use Modules\Users\Widgets\RecentUsersWidget;
use Modules\Users\Widgets\UsersTotalWidget;

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
        $permissions->declare('users.impersonate', roles: ['admin']);
    }

    protected function widgets(WidgetRegistry $widgets): void
    {
        $widgets->declare(
            module: $this->name(),
            key: 'users.total',
            resolver: UsersTotalWidget::class,
            permission: 'users.view',
            sort: 0,
        );

        $widgets->declare(
            module: $this->name(),
            key: 'users.recent',
            resolver: RecentUsersWidget::class,
            permission: 'users.view',
            sort: 10,
        );
    }
}
