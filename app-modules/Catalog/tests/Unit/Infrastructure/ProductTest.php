<?php

declare(strict_types=1);

use App\Domain\ValueObjects\Money;
use Illuminate\Support\Facades\Event;
use Modules\Catalog\Domain\Events\ProductPublished;
use Modules\Catalog\Domain\Exceptions\ProductAlreadyPublished;
use Modules\Catalog\Domain\ValueObjects\Sku;
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

it('drafts an unpublished product from valid values', function (): void {
    $product = Product::draft('Widget', new Sku(' widget-01 '), new Money(1250, 'eur'), null);

    expect($product->exists)->toBeFalse()
        ->and($product->sku)->toBe('WIDGET-01')
        ->and($product->price_cents)->toBe(1250)
        ->and($product->currency)->toBe('EUR')
        ->and($product->isPublished())->toBeFalse();
});

it('revises the details without touching publication', function (): void {
    $product = Product::factory()->published()->create();

    $product->revise('Gadget', new Sku('GADGET-02'), new Money(990, 'USD'), 'Shiny');

    expect($product->name)->toBe('Gadget')
        ->and($product->sku)->toBe('GADGET-02')
        ->and($product->description)->toBe('Shiny')
        ->and($product->isPublished())->toBeTrue();
});
