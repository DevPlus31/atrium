<?php

declare(strict_types=1);

use Modules\Catalog\Actions\DeleteProduct;
use Modules\Catalog\Infrastructure\Models\Product;
use Spatie\Activitylog\Models\Activity;

it('deletes a product and logs the activity', function (): void {
    $product = Product::factory()->create(['name' => 'Widget', 'sku' => 'WIDGET-01']);

    resolve(DeleteProduct::class)->handle($product);

    expect(Product::query()->whereKey($product->id)->exists())->toBeFalse();

    $activity = Activity::query()->where('event', 'deleted')->sole();

    expect($activity->getProperty('attributes'))->toBe(['name' => 'Widget', 'sku' => 'WIDGET-01']);
});
