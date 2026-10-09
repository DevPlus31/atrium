<?php

declare(strict_types=1);

namespace Modules\Catalog\Actions;

use App\Modules\AuditLog;
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

            AuditLog::record(
                log: 'catalog',
                event: 'published',
                subject: $product,
                properties: [
                    'attributes' => ['sku' => $product->sku],
                ],
            );

            return $product;
        });

        $product->flushDomainEvents();

        return $product;
    }
}
