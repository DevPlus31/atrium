<?php

declare(strict_types=1);

use Modules\Catalog\Infrastructure\Models\Product;

beforeEach(function (): void {
    $this->artisan('admin:sync-permissions')->assertSuccessful();
});

it('keeps guests and non-admins out', function (): void {
    assertAdminOnly('post', route('admin.products.publish', Product::factory()->create()));
});

it('publishes a draft product and redirects with a success flash', function (): void {
    $admin = adminUser();
    $product = Product::factory()->create();

    $response = $this->actingAs($admin)->post(route('admin.products.publish', $product));

    $response->assertRedirectToRoute('admin.products.index')
        ->assertToast('Product published.');

    expect($product->refresh()->published_at)->not->toBeNull();
});

it('refuses to publish a product twice', function (): void {
    $admin = adminUser();
    $product = Product::factory()->published()->create();

    $this->actingAs($admin)
        ->post(route('admin.products.publish', $product))
        ->assertForbidden();
});

it('forbids admins without the products.publish permission', function (): void {
    $admin = adminWithout('products.publish');
    $product = Product::factory()->create();

    $this->actingAs($admin)->post(route('admin.products.publish', $product))->assertForbidden();
});
