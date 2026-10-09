<?php

declare(strict_types=1);

namespace Modules\Roles\Providers;

use App\Models\User;
use App\Modules\ModuleServiceProvider;
use App\Modules\NavRegistry;
use App\Modules\PermissionRegistry;
use App\Modules\SearchRegistry;
use Illuminate\Support\Facades\Gate;
use Modules\Roles\Domain\Repositories\RoleRepository;
use Modules\Roles\Infrastructure\Repositories\EloquentRoleRepository;
use Modules\Roles\Policies\RolePolicy;
use Modules\Roles\Search\RolesSearch;
use Spatie\Permission\Models\Role;

final class RolesServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
        Gate::policy(Role::class, RolePolicy::class);

        $this->app->bind(RoleRepository::class, EloquentRoleRepository::class);
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
        $permissions->declare('roles.view', roles: [User::PANEL_ROLE]);
        $permissions->declare('roles.create', roles: [User::PANEL_ROLE]);
        $permissions->declare('roles.update', roles: [User::PANEL_ROLE]);
        $permissions->declare('roles.delete', roles: [User::PANEL_ROLE]);
    }

    protected function search(SearchRegistry $search): void
    {
        $search->add(
            module: $this->name(),
            label: 'Roles',
            searcher: RolesSearch::class,
            icon: 'shield',
            permission: 'roles.view',
            sort: 20,
        );
    }
}
