<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Modules\Catalog\Actions\PublishProduct;
use Modules\Catalog\Domain\Events\ProductPublished;
use Modules\Catalog\Domain\Exceptions\ProductAlreadyPublished;
use Modules\Catalog\Infrastructure\Models\Product;
use Spatie\Activitylog\Models\Activity;

it('publishes a draft product and dispatches the domain event after commit', function (): void {
    Event::fake([ProductPublished::class]);

    $product = Product::factory()->create(['sku' => 'WIDGET-01']);

    $published = resolve(PublishProduct::class)->handle($product);

    expect($published->published_at)->not->toBeNull()
        ->and(Activity::query()->where('event', 'published')->exists())->toBeTrue();

    Event::assertDispatched(
        ProductPublished::class,
        fn (ProductPublished $event): bool => $event->sku === 'WIDGET-01',
    );
});

it('rolls back and dispatches nothing when the product is already published', function (): void {
    Event::fake([ProductPublished::class]);

    $product = Product::factory()->published()->create();

    expect(fn (): Product => resolve(PublishProduct::class)->handle($product))
        ->toThrow(ProductAlreadyPublished::class);

    Event::assertNotDispatched(ProductPublished::class);

    expect(Activity::query()->where('event', 'published')->count())->toBe(0);
});

it('publishes only once even when a stale copy asks again', function (): void {
    $product = Product::factory()->create();
    $stale = Product::query()->findOrFail($product->id);

    resolve(PublishProduct::class)->handle($product);

    expect(fn () => resolve(PublishProduct::class)->handle($stale))->toThrow(ProductAlreadyPublished::class);
});
