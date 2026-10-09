<?php

declare(strict_types=1);

namespace Modules\Catalog\Actions;

use App\Modules\AuditLog;
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
            $product = $this->products->lockForUpdate($product);
            $this->products->delete($product);

            AuditLog::record(
                log: 'catalog',
                event: 'deleted',
                subject: $product,
                properties: [
                    'attributes' => ['name' => $product->name, 'sku' => $product->sku],
                ],
            );
        });
    }
}
