<?php

declare(strict_types=1);

namespace Modules\Catalog\Providers;

use App\Modules\ModuleServiceProvider;
use App\Modules\NavRegistry;
use App\Modules\PermissionRegistry;
use App\Modules\SearchRegistry;
use Modules\Catalog\Domain\Repositories\ProductRepository;
use Modules\Catalog\Infrastructure\Repositories\EloquentProductRepository;
use Modules\Catalog\Search\ProductsSearch;

final class CatalogServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ProductRepository::class, EloquentProductRepository::class);
    }

    protected function name(): string
    {
        return 'catalog';
    }

    protected function navigation(NavRegistry $nav): void
    {
        $nav->add(
            module: $this->name(),
            label: 'Products',
            routeName: 'admin.products.index',
            icon: 'package',
            permission: 'products.view',
            group: 'Catalog',
            sort: 30,
        );
    }

    protected function permissions(PermissionRegistry $permissions): void
    {
        $permissions->declare('products.view', roles: ['admin']);
        $permissions->declare('products.create', roles: ['admin']);
        $permissions->declare('products.update', roles: ['admin']);
        $permissions->declare('products.delete', roles: ['admin']);
        $permissions->declare('products.publish', roles: ['admin']);
    }

    protected function search(SearchRegistry $search): void
    {
        $search->add(
            module: $this->name(),
            label: 'Products',
            searcher: ProductsSearch::class,
            icon: 'package',
            permission: 'products.view',
            sort: 30,
        );
    }
}
