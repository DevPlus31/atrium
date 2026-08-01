<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Modules\Catalog\Domain\Events\ProductPublished;
use Modules\Catalog\Domain\Exceptions\ProductAlreadyPublished;
use Modules\Catalog\Infrastructure\Models\Product;

it('publishes a draft product and records the domain event', function (): void {
    Event::fake([ProductPublished::class]);

    $product = Product::factory()->create(['sku' => 'WIDGET-01']);

    $product->publish();
    $product->flushDomainEvents();

    expect($product->published_at)->not->toBeNull();

    Event::assertDispatched(
        ProductPublished::class,
        fn (ProductPublished $event): bool => $event->productId === $product->id && $event->sku === 'WIDGET-01',
    );
});

it('refuses to publish an already published product', function (): void {
    $product = Product::factory()->published()->create();

    expect(fn () => $product->publish())->toThrow(ProductAlreadyPublished::class);
});
