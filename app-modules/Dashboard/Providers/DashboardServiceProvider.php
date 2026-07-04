<?php

declare(strict_types=1);

namespace Modules\Dashboard\Providers;

use App\Modules\ModuleServiceProvider;
use App\Modules\NavRegistry;
use App\Modules\PermissionRegistry;

final class DashboardServiceProvider extends ModuleServiceProvider
{
    protected function name(): string
    {
        return 'dashboard';
    }

    protected function navigation(NavRegistry $nav): void
    {
        $nav->add(
            module: $this->name(),
            label: 'Dashboard',
            routeName: 'admin.dashboard.index',
            icon: 'layout-dashboard',
            permission: 'dashboard.view',
            sort: 0,
        );
    }

    protected function permissions(PermissionRegistry $permissions): void
    {
        $permissions->declare('dashboard.view', roles: ['admin']);
    }
}
