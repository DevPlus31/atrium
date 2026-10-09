<?php

declare(strict_types=1);

namespace Modules\Catalog\Actions;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Catalog\Infrastructure\Models\Product;

final readonly class DeleteProducts
{
    public function __construct(private DeleteProduct $deleteProduct)
    {
        //
    }

    /**
     * Delete each of the products, all or none, exactly as one by one.
     *
     * @param  Collection<int, Product>  $products
     */
    public function handle(Collection $products): void
    {
        DB::transaction(function () use ($products): void {
            foreach ($products as $product) {
                $this->deleteProduct->handle($product);
            }
        });
    }
}
