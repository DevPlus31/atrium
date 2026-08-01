<?php

declare(strict_types=1);

use App\Models\User;
use Modules\Catalog\Infrastructure\Models\Product;

beforeEach(function (): void {
    $this->artisan('admin:sync-permissions')->assertSuccessful();
});

function catalogSmokeAdmin(): User
{
    $user = User::factory()->create();
    $user->assignRole('admin');

    return $user;
}

it('renders the products index with seeded products', function (): void {
    $this->actingAs(catalogSmokeAdmin());

    Product::factory()->create(['name' => 'Blue Widget', 'sku' => 'BW-01']);
    Product::factory()->published()->create(['name' => 'Red Gadget', 'sku' => 'RG-01']);

    visit(route('admin.products.index'))
        ->assertSee('Products')
        ->assertSee('Blue Widget')
        ->assertSee('RG-01')
        ->assertNoJavaScriptErrors();
});

it('renders the products create page', function (): void {
    $this->actingAs(catalogSmokeAdmin());

    visit(route('admin.products.create'))
        ->assertSee('Create product')
        ->assertSee('SKU')
        ->assertNoJavaScriptErrors();
});

it('renders the edit page for a product', function (): void {
    $this->actingAs(catalogSmokeAdmin());

    $product = Product::factory()->create(['name' => 'Widget']);

    visit(route('admin.products.edit', $product))
        ->assertSee('Edit product')
        ->assertSee('Widget')
        ->assertNoJavaScriptErrors();
});
