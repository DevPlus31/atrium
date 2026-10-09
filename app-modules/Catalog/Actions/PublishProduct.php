<?php

declare(strict_types=1);

namespace Modules\Catalog\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Catalog\Domain\Repositories\ProductRepository;
use Modules\Catalog\Infrastructure\Models\Product;

final readonly class PublishProduct
{
    public function __construct(private ProductRepository $products)
    {
        //
    }

    public function handle(Product $product): Product
    {
        $product = DB::transaction(function () use ($product): Product {
            $product = $this->products->lockForUpdate($product);
            $product->publish();

            $this->products->save($product);

            activity('catalog')
                ->performedOn($product)
                ->event('published')
                ->withProperties([
                    'attributes' => ['sku' => $product->sku],
                ])
                ->log('published');

            return $product;
        });

        $product->flushDomainEvents();

        return $product;
    }
}
