<?php

declare(strict_types=1);

use Modules\Catalog\Infrastructure\Models\Product;

beforeEach(function (): void {
    $this->artisan('admin:sync-permissions')->assertSuccessful();
});

it('deletes the selected products', function (): void {
    Product::factory()->count(3)->create();
    $this->actingAs(adminUser());

    visit(route('admin.products.index'))
        ->click('[aria-label="Select all rows"]')
        ->assertSeeIn('[data-test="bulk-actions"]', '3 selected')
        ->click('[data-test="bulk-delete"]')
        ->click('[role="alertdialog"] button:has-text("Delete")')
        ->assertSee('3 products deleted.')
        ->assertSee('No products found.')
        ->assertNoJavaScriptErrors();

    expect(Product::query()->exists())->toBeFalse();
});
