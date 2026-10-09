<?php

declare(strict_types=1);

use Modules\Catalog\Infrastructure\Models\Product;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    $this->artisan('admin:sync-permissions')->assertSuccessful();
});

it('finds products by name or SKU', function (string $term): void {
    $product = Product::factory()->create(['name' => 'Brass lamp', 'sku' => 'LMP-0001']);

    $this->actingAs(adminUser())
        ->getJson(route('search', ['q' => $term]))
        ->assertOk()
        ->assertJsonPath('groups.0.label', 'Products')
        ->assertJsonPath('groups.0.results', [[
            'title' => 'Brass lamp',
            'description' => 'LMP-0001',
            'url' => route('admin.products.edit', $product),
        ]]);
})->with(['brass', 'LMP-0001']);

it('opens the filtered list without products.update', function (): void {
    Role::findByName('admin')->revokePermissionTo('products.update');
    Product::factory()->create(['name' => 'Brass lamp', 'sku' => 'LMP-0001']);

    $this->actingAs(adminUser())
        ->getJson(route('search', ['q' => 'brass']))
        ->assertJsonPath('groups.0.results.0.url', route('admin.products.index', ['filter' => ['search' => 'LMP-0001']]));
});
