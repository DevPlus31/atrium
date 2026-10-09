<?php

declare(strict_types=1);

use Modules\Catalog\Domain\Events\ProductPublished;

it('carries the product id and sku', function (): void {
    $event = new ProductPublished('product-id', 'WIDGET-01');

    expect($event->productId)->toBe('product-id')
        ->and($event->sku)->toBe('WIDGET-01');
});
