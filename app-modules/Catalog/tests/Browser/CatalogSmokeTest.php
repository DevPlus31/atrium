<?php

declare(strict_types=1);

use Modules\Catalog\Infrastructure\Models\Product;

beforeEach(function (): void {
    $this->artisan('admin:sync-permissions')->assertSuccessful();
});

it('renders the products index with seeded products', function (): void {
    $this->actingAs(adminUser());

    Product::factory()->create(['name' => 'Blue Widget', 'sku' => 'BW-01']);
    Product::factory()->published()->create(['name' => 'Red Gadget', 'sku' => 'RG-01']);

    visit(route('admin.products.index'))
        ->assertSee('Products')
        ->assertSee('Blue Widget')
        ->assertSee('RG-01')
        ->assertSee('Showing 1 to 2 of 2')
        ->navigate(route('admin.products.index', ['sort' => '-created_at']))
        ->assertScript("[...document.querySelectorAll('th')].find((th) => th.textContent.includes('Created'))?.getAttribute('aria-sort')", 'descending')
        ->assertScript("[...document.querySelectorAll('th')].find((th) => th.textContent.includes('Name'))?.getAttribute('aria-sort')", 'none')
        ->assertDontSee('of 2tal')
        ->assertNoJavaScriptErrors();
});

it('drops a pending search when the table is reset', function (): void {
    $this->actingAs(adminUser());

    Product::factory()->create(['name' => 'Blue Widget', 'sku' => 'BW-01']);
    Product::factory()->create(['name' => 'Red Gadget', 'sku' => 'RG-01']);

    visit(route('admin.products.index'))
        ->type('input[aria-label="Search products..."]', 'Blue')
        ->click('Reset')
        ->wait(1)
        ->assertQueryStringMissing('filter[search]')
        ->assertSee('Red Gadget')
        ->assertNoJavaScriptErrors();
});

it('deletes a product through the confirm dialog and toasts only once', function (): void {
    $this->actingAs(adminUser());

    Product::factory()->create(['name' => 'Blue Widget', 'sku' => 'BW-01']);

    visit(route('admin.products.index'))
        ->click('tbody tr:first-child [data-test="row-actions"]')
        ->click('Delete')
        ->assertSee('Delete product')
        ->click('Delete')
        ->assertSee('Product deleted.')
        ->assertDontSee('Blue Widget')
        ->wait(5)
        ->assertDontSee('Product deleted.')
        ->click('Orders')
        ->assertPathIs('/admin/orders')
        ->back()
        ->assertPathIs('/admin/products')
        ->wait(1)
        ->assertDontSee('Product deleted.')
        ->assertNoJavaScriptErrors();
});

it('renders the products create page', function (): void {
    $this->actingAs(adminUser());

    visit(route('admin.products.create'))
        ->assertSee('Create product')
        ->assertSee('SKU')
        ->assertNoJavaScriptErrors();
});

it('renders the edit page for a product', function (): void {
    $this->actingAs(adminUser());

    $product = Product::factory()->create(['name' => 'Widget']);

    visit(route('admin.products.edit', $product))
        ->assertSee('Edit product')
        ->assertSee('Widget')
        ->assertNoJavaScriptErrors();
});

it('renders the products page under the content security policy', function (): void {
    $this->actingAs(adminUser());

    visit(route('admin.products.index'))
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors();
});
