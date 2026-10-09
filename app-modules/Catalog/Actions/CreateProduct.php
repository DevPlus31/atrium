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
        $sku = (string) new Sku($sku);
        $money = new Money($priceCents, $currency);

        return DB::transaction(function () use ($name, $sku, $money, $description): Product {
            $product = new Product([
                'name' => $name,
                'sku' => $sku,
                'price_cents' => $money->amount,
                'currency' => $money->currency,
                'description' => $description,
            ]);

            $this->products->save($product);

            activity('catalog')
                ->performedOn($product)
                ->event('created')
                ->withProperties([
                    'attributes' => ['name' => $name, 'sku' => $sku],
                ])
                ->log('created');

            return $product;
        });
    }
}
