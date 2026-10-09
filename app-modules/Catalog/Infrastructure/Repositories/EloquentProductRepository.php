<?php

declare(strict_types=1);

namespace Modules\Catalog\Infrastructure\Repositories;

use Illuminate\Database\UniqueConstraintViolationException;
use Modules\Catalog\Domain\Exceptions\SkuAlreadyTaken;
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
        try {
            $product->save();
        } catch (UniqueConstraintViolationException) {
            // The form checks the SKU is free; this is two saves racing for it.
            throw SkuAlreadyTaken::forSku($product->sku);
        }
    }

    public function delete(Product $product): void
    {
        $product->delete();
    }
}
