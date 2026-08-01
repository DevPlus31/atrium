<?php

declare(strict_types=1);

namespace Modules\Catalog\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Catalog\Domain\Repositories\ProductRepository;
use Modules\Catalog\Infrastructure\Models\Product;

final readonly class DeleteProduct
{
    public function __construct(private ProductRepository $products)
    {
        //
    }

    public function handle(Product $product): void
    {
        DB::transaction(function () use ($product): void {
            $this->products->delete($product);

            activity('catalog')
                ->performedOn($product)
                ->event('deleted')
                ->withProperties([
                    'attributes' => ['name' => $product->name, 'sku' => $product->sku],
                ])
                ->log('deleted');
        });
    }
}
