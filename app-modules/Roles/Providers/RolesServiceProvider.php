<?php

declare(strict_types=1);

namespace Modules\Roles\Providers;

use App\Modules\ModuleServiceProvider;
use App\Modules\NavRegistry;
use App\Modules\PermissionRegistry;
use Illuminate\Support\Facades\Gate;
use Modules\Roles\Policies\RolePolicy;
use Spatie\Permission\Models\Role;

final class RolesServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
        Gate::policy(Role::class, RolePolicy::class);
    }

    protected function name(): string
    {
        return 'roles';
    }

    protected function navigation(NavRegistry $nav): void
    {
        $nav->add(
            module: $this->name(),
            label: 'Roles',
            routeName: 'admin.roles.index',
            icon: 'shield',
            permission: 'roles.view',
            group: 'Management',
            sort: 20,
        );
    }

    protected function permissions(PermissionRegistry $permissions): void
    {
        $permissions->declare('roles.view', roles: ['admin']);
        $permissions->declare('roles.create', roles: ['admin']);
        $permissions->declare('roles.update', roles: ['admin']);
        $permissions->declare('roles.delete', roles: ['admin']);
    }
}
