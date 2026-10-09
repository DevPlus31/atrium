<?php

declare(strict_types=1);

use Modules\Catalog\Domain\Repositories\ProductRepository;
use Modules\Catalog\Infrastructure\Models\Product;
use Modules\Catalog\Infrastructure\Repositories\EloquentProductRepository;

it('resolves to the eloquent implementation', function (): void {
    expect(resolve(ProductRepository::class))->toBeInstanceOf(EloquentProductRepository::class);
});

it('persists and deletes a product', function (): void {
    $repository = resolve(ProductRepository::class);
    $product = Product::factory()->make();

    $repository->save($product);

    expect(Product::query()->whereKey($product->id)->exists())->toBeTrue();

    $repository->delete($product);

    expect(Product::query()->whereKey($product->id)->exists())->toBeFalse();
});

it('re-reads a product under a row lock', function (): void {
    $product = Product::factory()->create();

    expect(resolve(ProductRepository::class)->lockForUpdate($product)->is($product))->toBeTrue();
});
