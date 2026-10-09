<?php

declare(strict_types=1);

namespace Modules\Catalog\Infrastructure\Repositories;

use Modules\Catalog\Domain\Repositories\ProductRepository;
use Modules\Catalog\Infrastructure\Models\Product;

final readonly class EloquentProductRepository implements ProductRepository
{
    public function lockForUpdate(Product $product): Product
    {
        return Product::query()->whereKey($product->getKey())->lockForUpdate()->firstOrFail();
    }

    public function save(Product $product): void
    {
        $product->save();
    }

    public function delete(Product $product): void
    {
        $product->delete();
    }
}
