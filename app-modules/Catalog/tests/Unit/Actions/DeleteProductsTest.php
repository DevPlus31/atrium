<?php

declare(strict_types=1);

use Modules\Catalog\Actions\DeleteProducts;
use Modules\Catalog\Infrastructure\Models\Product;

it('deletes every given product', function (): void {
    $products = Product::factory()->count(2)->create();

    resolve(DeleteProducts::class)->handle($products);

    expect(Product::query()->exists())->toBeFalse();
});
