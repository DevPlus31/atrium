<?php

declare(strict_types=1);

use Modules\Catalog\Actions\CreateProduct;
use Modules\Catalog\Domain\Exceptions\InvalidSkuException;
use Modules\Catalog\Infrastructure\Models\Product;
use Spatie\Activitylog\Models\Activity;

it('creates a product with normalized value objects and logs the activity', function (): void {
    $product = resolve(CreateProduct::class)->handle(
        name: 'Widget',
        sku: 'widget-01',
        priceCents: 1500,
        currency: 'usd',
        description: 'A fine widget.',
    );

    expect($product->sku)->toBe('WIDGET-01')
        ->and($product->currency)->toBe('USD')
        ->and($product->price_cents)->toBe(1500)
        ->and(Product::query()->whereKey($product->id)->exists())->toBeTrue();

    $activity = Activity::query()->where('event', 'created')->sole();

    expect($activity->log_name)->toBe('catalog')
        ->and($activity->getProperty('attributes'))->toBe(['name' => 'Widget', 'sku' => 'WIDGET-01']);
});

it('rejects an invalid sku before creating anything', function (): void {
    expect(fn (): Product => resolve(CreateProduct::class)->handle(
        name: 'Widget',
        sku: '!',
        priceCents: 100,
        currency: 'USD',
        description: null,
    ))->toThrow(InvalidSkuException::class);

    expect(Product::query()->count())->toBe(0);
});
