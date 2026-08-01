<?php

declare(strict_types=1);

namespace Modules\Catalog\Infrastructure\Repositories;

use Modules\Catalog\Domain\Repositories\ProductRepository;
use Modules\Catalog\Infrastructure\Models\Product;

final readonly class EloquentProductRepository implements ProductRepository
{
    public function save(Product $product): void
    {
        $product->save();
    }

    public function delete(Product $product): void
    {
        $product->delete();
    }
}
