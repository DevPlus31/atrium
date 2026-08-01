<?php

declare(strict_types=1);

use Modules\Catalog\Actions\UpdateProduct;
use Modules\Catalog\Infrastructure\Models\Product;
use Spatie\Activitylog\Models\Activity;

it('updates a product with normalized value objects and logs the activity', function (): void {
    $product = Product::factory()->create(['name' => 'Old', 'sku' => 'OLD-01']);

    $updated = resolve(UpdateProduct::class)->handle(
        product: $product,
        name: 'New',
        sku: 'new-01',
        priceCents: 2000,
        currency: 'eur',
        description: null,
    );

    expect($updated->name)->toBe('New')
        ->and($updated->sku)->toBe('NEW-01')
        ->and($updated->currency)->toBe('EUR')
        ->and($updated->price_cents)->toBe(2000)
        ->and(Activity::query()->where('event', 'updated')->exists())->toBeTrue();
});
