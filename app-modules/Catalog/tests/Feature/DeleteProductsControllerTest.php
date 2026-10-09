<?php

declare(strict_types=1);

use Modules\Catalog\Infrastructure\Models\Product;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    $this->artisan('admin:sync-permissions')->assertSuccessful();
});

it('deletes the selected products', function (): void {
    $products = Product::factory()->count(2)->create();
    $kept = Product::factory()->create();

    $this->actingAs(adminUser())
        ->delete(route('admin.products.bulk-destroy'), ['ids' => $products->pluck('id')->all()])
        ->assertRedirectToRoute('admin.products.index')
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => '2 products deleted.']);

    expect(Product::query()->pluck('id')->all())->toBe([$kept->id]);
});

it('needs products.delete', function (): void {
    Role::findByName('admin')->revokePermissionTo('products.delete');
    $product = Product::factory()->create();

    $this->actingAs(adminUser())
        ->delete(route('admin.products.bulk-destroy'), ['ids' => [$product->id]])
        ->assertForbidden();

    expect($product->fresh())->not->toBeNull();
});

it('redirects guests to the login page', function (): void {
    $this->delete(route('admin.products.bulk-destroy'), ['ids' => ['x']])->assertRedirectToRoute('login');
});

it('validates the selection', function (mixed $ids): void {
    $this->actingAs(adminUser())
        ->delete(route('admin.products.bulk-destroy'), ['ids' => $ids])
        ->assertSessionHasErrors('ids');
})->with([
    'nothing' => [[]],
    'more than a page' => [array_map(strval(...), range(1, 101))],
]);
