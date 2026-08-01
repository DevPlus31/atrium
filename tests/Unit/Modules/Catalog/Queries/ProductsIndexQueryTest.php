<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Modules\Catalog\Infrastructure\Models\Product;
use Modules\Catalog\Queries\ProductsIndexQuery;
use Spatie\QueryBuilder\Exceptions\InvalidSortQuery;

/**
 * @param  array<string, mixed>  $query
 */
function productsIndexQuery(array $query = []): ProductsIndexQuery
{
    return new ProductsIndexQuery(Request::create('/admin/products', 'GET', $query));
}

it('filters by search on the name or sku', function (): void {
    Product::factory()->create(['name' => 'Blue Widget', 'sku' => 'BW-01']);
    Product::factory()->create(['name' => 'Red Gadget', 'sku' => 'RG-01']);

    $byName = productsIndexQuery(['filter' => ['search' => 'Widget']])->builder()->get();
    $bySku = productsIndexQuery(['filter' => ['search' => 'RG']])->builder()->get();

    expect($byName->pluck('sku')->all())->toBe(['BW-01'])
        ->and($bySku->pluck('sku')->all())->toBe(['RG-01']);
});

it('filters by published and draft status', function (): void {
    Product::factory()->published()->create(['sku' => 'PUB-01']);
    Product::factory()->create(['sku' => 'DRAFT-01']);

    $published = productsIndexQuery(['filter' => ['status' => 'published']])->builder()->get();
    $draft = productsIndexQuery(['filter' => ['status' => 'draft']])->builder()->get();

    expect($published->pluck('sku')->all())->toBe(['PUB-01'])
        ->and($draft->pluck('sku')->all())->toBe(['DRAFT-01']);
});

it('sorts by the allowed columns', function (): void {
    Product::factory()->create(['name' => 'Bravo', 'sku' => 'B-01', 'price_cents' => 200]);
    Product::factory()->create(['name' => 'Alpha', 'sku' => 'A-01', 'price_cents' => 100]);

    $byName = productsIndexQuery(['sort' => 'name'])->builder()->get();
    $byPrice = productsIndexQuery(['sort' => 'price_cents'])->builder()->get();
    $bySku = productsIndexQuery(['sort' => 'sku'])->builder()->get();

    expect($byName->first()?->name)->toBe('Alpha')
        ->and($byPrice->first()?->price_cents)->toBe(100)
        ->and($bySku->first()?->sku)->toBe('A-01');
});

it('rejects sorts outside the whitelist', function (): void {
    Product::factory()->create();

    expect(fn () => productsIndexQuery(['sort' => 'description'])->builder()->get())
        ->toThrow(InvalidSortQuery::class);
});

it('sorts by newest first by default', function (): void {
    $older = Product::factory()->create(['sku' => 'OLD-01']);
    Product::factory()->create(['sku' => 'NEW-01']);

    Product::query()->whereKey($older->id)->update(['created_at' => now()->subDay()]);

    expect(productsIndexQuery()->builder()->get()->first()?->sku)->toBe('NEW-01');
});

it('paginates with a default of 15 and caps per_page within bounds', function (): void {
    Product::factory()->create();

    expect(productsIndexQuery()->paginate()->perPage())->toBe(15)
        ->and(productsIndexQuery(['per_page' => 25])->paginate()->perPage())->toBe(25)
        ->and(productsIndexQuery(['per_page' => 500])->paginate()->perPage())->toBe(100)
        ->and(productsIndexQuery(['per_page' => 0])->paginate()->perPage())->toBe(1);
});
