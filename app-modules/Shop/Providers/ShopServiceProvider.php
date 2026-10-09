<?php

declare(strict_types=1);

namespace Modules\Shop\Providers;

use App\Enums\Area;
use App\Models\User;
use App\Modules\ModuleServiceProvider;
use App\Modules\NavRegistry;
use App\Modules\PermissionRegistry;
use App\Modules\SearchRegistry;
use App\Modules\WidgetRegistry;
use Modules\Shop\Domain\Repositories\OrderRepository;
use Modules\Shop\Infrastructure\Repositories\EloquentOrderRepository;
use Modules\Shop\Search\OrdersSearch;
use Modules\Shop\Widgets\MemberOrdersWidget;

final class ShopServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
        $this->app->bind(OrderRepository::class, EloquentOrderRepository::class);
    }

    protected function name(): string
    {
        return 'shop';
    }

    protected function navigation(NavRegistry $nav): void
    {
        $nav->add(
            module: $this->name(),
            label: 'Orders',
            routeName: 'admin.orders.index',
            icon: 'receipt',
            permission: 'orders.view',
            group: 'Shop',
            sort: 10,
        );

        // A module's own settings join the shared "Settings" menu group.
        $nav->add(
            module: $this->name(),
            label: 'Shop',
            routeName: 'admin.shop.settings.edit',
            icon: 'shopping-cart',
            permission: 'shop.settings.update',
            group: 'Settings',
            sort: 20,
        );
    }

    protected function permissions(PermissionRegistry $permissions): void
    {
        $permissions->declare('orders.view', roles: [User::PANEL_ROLE]);
        $permissions->declare('orders.create', roles: [User::PANEL_ROLE]);
        $permissions->declare('orders.update', roles: [User::PANEL_ROLE]);
        $permissions->declare('orders.delete', roles: [User::PANEL_ROLE]);
        $permissions->declare('shop.settings.update', roles: [User::PANEL_ROLE]);
    }

    protected function widgets(WidgetRegistry $widgets): void
    {
        $widgets->declare(
            module: $this->name(),
            key: 'shop.my-orders',
            resolver: MemberOrdersWidget::class,
            sort: 20,
            area: Area::Member,
        );
    }

    protected function search(SearchRegistry $search): void
    {
        $search->add(
            module: $this->name(),
            label: 'Orders',
            searcher: OrdersSearch::class,
            icon: 'receipt',
            permission: 'orders.view',
            sort: 40,
        );
    }
}
