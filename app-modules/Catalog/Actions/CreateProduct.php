<?php

declare(strict_types=1);

namespace Modules\Catalog\Actions;

use App\Domain\ValueObjects\Money;
use Illuminate\Support\Facades\DB;
use Modules\Catalog\Domain\Repositories\ProductRepository;
use Modules\Catalog\Domain\ValueObjects\Sku;
use Modules\Catalog\Infrastructure\Models\Product;

final readonly class CreateProduct
{
    public function __construct(private ProductRepository $products)
    {
        //
    }

    public function handle(string $name, string $sku, int $priceCents, string $currency, ?string $description): Product
    {
        $product = Product::draft($name, new Sku($sku), new Money($priceCents, $currency), $description);

        return DB::transaction(function () use ($product): Product {
            $this->products->save($product);

            activity('catalog')
                ->performedOn($product)
                ->event('created')
                ->withProperties([
                    'attributes' => ['name' => $product->name, 'sku' => $product->sku],
                ])
                ->log('created');

            return $product;
        });
    }
}
