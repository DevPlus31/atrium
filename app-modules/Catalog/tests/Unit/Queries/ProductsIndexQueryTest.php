<?php

declare(strict_types=1);

use Modules\Catalog\Infrastructure\Models\Product;
use Modules\Catalog\Queries\ProductsIndexQuery;
use Spatie\QueryBuilder\Exceptions\InvalidSortQuery;

it('filters by search on the name or sku', function (): void {
    Product::factory()->create(['name' => 'Blue Widget', 'sku' => 'BW-01']);
    Product::factory()->create(['name' => 'Red Gadget', 'sku' => 'RG-01']);

    $byName = indexQuery(ProductsIndexQuery::class, ['filter' => ['search' => 'Widget']])->builder()->get();
    $bySku = indexQuery(ProductsIndexQuery::class, ['filter' => ['search' => 'RG']])->builder()->get();

    expect($byName->pluck('sku')->all())->toBe(['BW-01'])
        ->and($bySku->pluck('sku')->all())->toBe(['RG-01']);
});

it('filters by published and draft status', function (): void {
    Product::factory()->published()->create(['sku' => 'PUB-01']);
    Product::factory()->create(['sku' => 'DRAFT-01']);

    $published = indexQuery(ProductsIndexQuery::class, ['filter' => ['status' => 'published']])->builder()->get();
    $draft = indexQuery(ProductsIndexQuery::class, ['filter' => ['status' => 'draft']])->builder()->get();

    expect($published->pluck('sku')->all())->toBe(['PUB-01'])
        ->and($draft->pluck('sku')->all())->toBe(['DRAFT-01']);
});

it('lists every product when both statuses are selected', function (): void {
    Product::factory()->published()->create();
    Product::factory()->create();

    expect(indexQuery(ProductsIndexQuery::class, ['filter' => ['status' => 'draft,published']])->builder()->count())->toBe(2)
        ->and(indexQuery(ProductsIndexQuery::class, ['filter' => ['status' => 'unknown']])->builder()->count())->toBe(2);
});

it('sorts by the allowed columns', function (): void {
    Product::factory()->create(['name' => 'Bravo', 'sku' => 'B-01', 'price_cents' => 200]);
    Product::factory()->create(['name' => 'Alpha', 'sku' => 'A-01', 'price_cents' => 100]);

    $byName = indexQuery(ProductsIndexQuery::class, ['sort' => 'name'])->builder()->get();
    $byPrice = indexQuery(ProductsIndexQuery::class, ['sort' => 'price_cents'])->builder()->get();
    $bySku = indexQuery(ProductsIndexQuery::class, ['sort' => 'sku'])->builder()->get();

    expect($byName->first()?->name)->toBe('Alpha')
        ->and($byPrice->first()?->price_cents)->toBe(100)
        ->and($bySku->first()?->sku)->toBe('A-01');
});

it('rejects sorts outside the whitelist', function (): void {
    Product::factory()->create();

    expect(fn () => indexQuery(ProductsIndexQuery::class, ['sort' => 'description'])->builder()->get())
        ->toThrow(InvalidSortQuery::class);
});

it('sorts by newest first by default', function (): void {
    $older = Product::factory()->create(['sku' => 'OLD-01']);
    Product::factory()->create(['sku' => 'NEW-01']);

    Product::query()->whereKey($older->id)->update(['created_at' => now()->subDay()]);

    expect(indexQuery(ProductsIndexQuery::class)->builder()->get()->first()?->sku)->toBe('NEW-01');
});
