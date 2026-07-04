<?php

declare(strict_types=1);

namespace Tests\Fixtures\Modules\TestModule\Providers;

use App\Modules\ModuleServiceProvider;
use App\Modules\NavRegistry;
use App\Modules\PermissionRegistry;
use App\Modules\WidgetRegistry;
use Tests\Fixtures\Modules\TestModule\Widgets\TestModuleWidget;

final class TestModuleServiceProvider extends ModuleServiceProvider
{
    protected function name(): string
    {
        return 'test-module';
    }

    protected function navigation(NavRegistry $nav): void
    {
        $nav->add(
            module: $this->name(),
            label: 'Test Module',
            routeName: 'admin.test-module.index',
            icon: 'boxes',
            permission: 'test-module.view',
            group: 'Modules',
            sort: 10,
        );
    }

    protected function permissions(PermissionRegistry $permissions): void
    {
        $permissions->declare('test-module.view', roles: ['admin']);
    }

    protected function widgets(WidgetRegistry $widgets): void
    {
        $widgets->declare(
            module: $this->name(),
            key: 'test-module.stats',
            resolver: TestModuleWidget::class,
            permission: 'test-module.view',
            sort: 5,
        );
    }
}
