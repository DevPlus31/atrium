<?php

declare(strict_types=1);

use Modules\Catalog\Infrastructure\Models\Product;

beforeEach(function (): void {
    $this->artisan('admin:sync-permissions')->assertSuccessful();
});

it('deletes the selected products', function (): void {
    $products = Product::factory()->count(2)->create();
    $kept = Product::factory()->create();

    $this->actingAs(adminUser())
        ->delete(route('admin.products.bulk-destroy'), ['ids' => $products->pluck('id')->all()])
        ->assertRedirectToRoute('admin.products.index')
        ->assertToast('2 products deleted.');

    expect(Product::query()->pluck('id')->all())->toBe([$kept->id]);
});

it('needs products.delete', function (): void {
    $product = Product::factory()->create();

    $this->actingAs(adminWithout('products.delete'))
        ->delete(route('admin.products.bulk-destroy'), ['ids' => [$product->id]])
        ->assertForbidden();

    expect($product->fresh())->not->toBeNull();
});

it('keeps guests and non-admins out', function (): void {
    assertAdminOnly('delete', route('admin.products.bulk-destroy'), ['ids' => ['x']]);
});

it('validates the selection', function (mixed $ids): void {
    $this->actingAs(adminUser())
        ->delete(route('admin.products.bulk-destroy'), ['ids' => $ids])
        ->assertSessionHasErrors('ids');
})->with(invalidBulkSelections());
