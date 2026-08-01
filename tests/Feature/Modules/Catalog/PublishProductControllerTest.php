<?php

declare(strict_types=1);

use App\Models\User;
use Modules\Catalog\Infrastructure\Models\Product;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    $this->withoutVite();

    $this->artisan('admin:sync-permissions')->assertSuccessful();
});

function publishModuleAdmin(): User
{
    $user = User::factory()->create();
    $user->assignRole('admin');

    return $user;
}

it('redirects guests to the login page', function (): void {
    $this->post('/admin/products/00000000-0000-0000-0000-000000000000/publish')
        ->assertRedirectToRoute('login');
});

it('publishes a draft product and redirects with a success flash', function (): void {
    $admin = publishModuleAdmin();
    $product = Product::factory()->create();

    $response = $this->actingAs($admin)->post(route('admin.products.publish', $product));

    $response->assertRedirectToRoute('admin.products.index')
        ->assertSessionHas('success', 'Product published.');

    expect($product->refresh()->published_at)->not->toBeNull();
});

it('flashes an error when the product is already published', function (): void {
    $admin = publishModuleAdmin();
    $product = Product::factory()->published()->create();

    $response = $this->actingAs($admin)
        ->from(route('admin.products.index'))
        ->post(route('admin.products.publish', $product));

    $response->assertRedirect(route('admin.products.index'))
        ->assertSessionHas('error');
});

it('forbids admins without the products.publish permission', function (): void {
    Role::findByName('admin')->revokePermissionTo('products.publish');
    $admin = publishModuleAdmin();
    $product = Product::factory()->create();

    $this->actingAs($admin)->post(route('admin.products.publish', $product))->assertForbidden();
});
