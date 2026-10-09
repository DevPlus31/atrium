<?php

declare(strict_types=1);

namespace Modules\Dashboard\Providers;

use App\Enums\Area;
use App\Modules\ModuleServiceProvider;
use App\Modules\NavRegistry;
use App\Modules\PermissionRegistry;
use App\Modules\WidgetRegistry;
use Modules\Dashboard\Widgets\AccessStatusWidget;
use Modules\Dashboard\Widgets\AccountOverviewWidget;

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

        $nav->add(
            module: $this->name(),
            label: 'Home',
            routeName: 'member.dashboard',
            icon: 'house',
            sort: 0,
            area: Area::Member,
        );
    }

    protected function permissions(PermissionRegistry $permissions): void
    {
        $permissions->declare('dashboard.view', roles: ['admin']);
    }

    protected function widgets(WidgetRegistry $widgets): void
    {
        $widgets->declare(
            module: $this->name(),
            key: 'dashboard.access',
            resolver: AccessStatusWidget::class,
            sort: 0,
            area: Area::Member,
        );

        $widgets->declare(
            module: $this->name(),
            key: 'dashboard.account',
            resolver: AccountOverviewWidget::class,
            sort: 10,
            area: Area::Member,
        );
    }
}
