<?php

declare(strict_types=1);

namespace Modules\Catalog\Actions;

use App\Domain\ValueObjects\Money;
use Illuminate\Support\Facades\DB;
use Modules\Catalog\Domain\Repositories\ProductRepository;
use Modules\Catalog\Domain\ValueObjects\Sku;
use Modules\Catalog\Infrastructure\Models\Product;

final readonly class UpdateProduct
{
    public function __construct(private ProductRepository $products)
    {
        //
    }

    public function handle(Product $product, string $name, string $sku, int $priceCents, string $currency, ?string $description): Product
    {
        $sku = new Sku($sku);
        $price = new Money($priceCents, $currency);

        return DB::transaction(function () use ($product, $name, $sku, $price, $description): Product {
            $product = $this->products->lockForUpdate($product);
            $product->revise($name, $sku, $price, $description);

            $this->products->save($product);

            activity('catalog')
                ->performedOn($product)
                ->event('updated')
                ->withProperties([
                    'attributes' => ['name' => $product->name, 'sku' => $product->sku],
                ])
                ->log('updated');

            return $product;
        });
    }
}
