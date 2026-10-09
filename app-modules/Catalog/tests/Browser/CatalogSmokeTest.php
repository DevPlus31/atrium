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

    $page = visit(route('admin.products.index'));
    $page->script("window.__visits = 0; document.addEventListener('inertia:start', () => window.__visits++)");

    $page->type('input[aria-label="Search products..."]', 'Blue')
        ->click('Reset')
        ->assertQueryStringMissing('filter[search]')
        ->assertSee('Red Gadget');

    // Absence of the debounced search can only show once its window (400ms
    // in use-table-state.ts) has passed: wait it out on the page's own clock,
    // then expect the reset's visit alone.
    expect($page->script('new Promise((resolve) => setTimeout(() => resolve(window.__visits), 600))'))->toBe(1);

    $page->assertQueryStringMissing('filter[search]')
        ->assertNoJavaScriptErrors();
});

it('deletes a product through the confirm dialog and toasts only once', function (): void {
    $this->actingAs(adminUser());

    Product::factory()->create(['name' => 'Blue Widget', 'sku' => 'BW-01']);

    $page = visit(route('admin.products.index'));

    // Count every "Product deleted." toast that enters the page, so a replay
    // of the flash after navigating back shows up as a second one.
    $page->script(<<<'JS'
        window.__deletedToasts = 0;
        new MutationObserver((records) => {
            for (const record of records) {
                for (const node of record.addedNodes) {
                    if (!(node instanceof HTMLElement)) continue;
                    const toasts = [node, ...node.querySelectorAll('[data-sonner-toast]')]
                        .filter((el) => el.matches('[data-sonner-toast]') && el.textContent.includes('Product deleted.'));
                    window.__deletedToasts += toasts.length;
                }
            }
        }).observe(document.body, { childList: true, subtree: true });
        JS);

    $page->click('tbody tr:first-child [data-test="row-actions"]')
        ->click('Delete')
        ->assertSee('Delete product')
        ->click('Delete')
        ->assertSee('Product deleted.')
        ->assertDontSee('Blue Widget')
        ->click('Orders')
        ->assertPathIs('/admin/orders')
        ->back()
        ->assertPathIs('/admin/products')
        ->assertSee('No products found.')
        ->assertScript('window.__deletedToasts', 1)
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
