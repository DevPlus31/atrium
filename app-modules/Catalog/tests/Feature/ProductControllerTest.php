<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\NavRegistry;
use Inertia\Testing\AssertableInertia;
use Modules\Catalog\Infrastructure\Models\Product;
use Spatie\Activitylog\Models\Activity;

beforeEach(function (): void {
    $this->artisan('admin:sync-permissions')->assertSuccessful();
});

it('keeps guests and non-admins out', function (string $method, Closure $url): void {
    assertAdminOnly($method, $url());
})->with([
    'index' => ['get', fn (): string => route('admin.products.index')],
    'create' => ['get', fn (): string => route('admin.products.create')],
    'store' => ['post', fn (): string => route('admin.products.store')],
    'edit' => ['get', fn (): string => route('admin.products.edit', Product::factory()->create())],
    'update' => ['put', fn (): string => route('admin.products.update', Product::factory()->create())],
    'destroy' => ['delete', fn (): string => route('admin.products.destroy', Product::factory()->create())],
]);

it('forbids admins without the products.view permission', function (): void {
    $this->actingAs(adminWithout('products.view'))->get(route('admin.products.index'))->assertForbidden();
});

it('registers the products nav item for permitted admins', function (): void {
    $admin = adminUser();

    $navItems = $this->app->make(NavRegistry::class)->itemsFor($admin);
    $navItem = collect($navItems)->firstWhere('label', 'Products');

    expect($navItem)->not->toBeNull()
        ->and($navItem?->href)->toBe(route('admin.products.index'))
        ->and($navItem?->group)->toBe('Catalog')
        ->and($navItem?->icon)->toBe('package');
});

it('hides the products nav item without the products.view permission', function (): void {
    $navItems = $this->app->make(NavRegistry::class)->itemsFor(User::factory()->create());

    expect(collect($navItems)->firstWhere('label', 'Products'))->toBeNull();
});

it('renders the index with per-row abilities', function (): void {
    $admin = adminUser();
    Product::factory()->create(['name' => 'Widget', 'sku' => 'WIDGET-01']);

    $response = $this->actingAs($admin)->get(route('admin.products.index'));

    $response->assertOk()->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
        ->component('catalog::index')
        ->has('products.data', 1)
        ->where('products.meta.per_page', 15)
        ->where('products.data.0.sku', 'WIDGET-01')
        ->where('products.data.0.can.update', true)
        ->where('products.data.0.can.delete', true)
        ->where('products.data.0.can.publish', true));
});

it('applies the search filter to the index', function (): void {
    $admin = adminUser();
    Product::factory()->create(['sku' => 'ALPHA-01']);
    Product::factory()->create(['sku' => 'BETA-01']);

    $response = $this->actingAs($admin)->get(route('admin.products.index', ['filter' => ['search' => 'ALPHA']]));

    $response->assertOk()->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
        ->has('products.data', 1)
        ->where('products.data.0.sku', 'ALPHA-01'));
});

it('renders the create page', function (): void {
    $this->actingAs(adminUser())->get(route('admin.products.create'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page->component('catalog::create'));
});

it('stores a product and redirects with a success flash', function (): void {
    $admin = adminUser();

    $response = $this->actingAs($admin)
        ->fromRoute('admin.products.create')
        ->post(route('admin.products.store'), [
            'name' => 'Widget',
            'sku' => 'widget-01',
            'price_cents' => 1500,
            'currency' => 'USD',
            'description' => 'A fine widget.',
        ]);

    $response->assertRedirectToRoute('admin.products.index')
        ->assertToast('Product created.');

    expect(Product::query()->where('sku', 'WIDGET-01')->exists())->toBeTrue()
        ->and(Activity::query()->where('event', 'created')->where('causer_id', $admin->id)->exists())->toBeTrue();
});

it('validates the store request', function (): void {
    $admin = adminUser();
    Product::factory()->create(['sku' => 'TAKEN-01']);

    $missing = $this->actingAs($admin)
        ->fromRoute('admin.products.create')
        ->post(route('admin.products.store'), []);

    $missing->assertRedirectToRoute('admin.products.create')
        ->assertSessionHasErrors(['name', 'sku', 'price_cents', 'currency']);

    $invalid = $this->actingAs($admin)
        ->fromRoute('admin.products.create')
        ->post(route('admin.products.store'), [
            'name' => 'Widget',
            'sku' => 'TAKEN-01',
            'price_cents' => -1,
            'currency' => 'US',
        ]);

    $invalid->assertRedirectToRoute('admin.products.create')
        ->assertSessionHasErrors(['sku', 'price_cents', 'currency']);

    expect(Product::query()->count())->toBe(1);
});

it('validates a single field precognitively without side effects', function (): void {
    $admin = adminUser();

    $response = $this->actingAs($admin)
        ->withHeaders(['Precognition' => 'true', 'Precognition-Validate-Only' => 'name'])
        ->postJson(route('admin.products.store'), ['name' => '']);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name'])
        ->assertJsonMissingValidationErrors(['sku']);

    expect(Product::query()->count())->toBe(0);
});

it('renders the edit page', function (): void {
    $admin = adminUser();
    $product = Product::factory()->create(['sku' => 'WIDGET-01']);

    $response = $this->actingAs($admin)->get(route('admin.products.edit', $product));

    $response->assertOk()->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
        ->component('catalog::edit')
        ->where('product.id', $product->id)
        ->where('product.sku', 'WIDGET-01'));
});

it('updates a product and redirects with a success flash', function (): void {
    $admin = adminUser();
    $product = Product::factory()->create(['name' => 'Old', 'sku' => 'OLD-01']);

    $response = $this->actingAs($admin)
        ->fromRoute('admin.products.edit', $product)
        ->put(route('admin.products.update', $product), [
            'name' => 'New',
            'sku' => 'NEW-01',
            'price_cents' => 2000,
            'currency' => 'EUR',
            'description' => null,
        ]);

    $response->assertRedirectToRoute('admin.products.index')
        ->assertToast('Product updated.');

    expect($product->refresh()->name)->toBe('New')
        ->and($product->sku)->toBe('NEW-01');
});

it('allows keeping the same sku on update but rejects a duplicate', function (): void {
    $admin = adminUser();
    $product = Product::factory()->create(['sku' => 'MINE-01']);
    Product::factory()->create(['sku' => 'OTHER-01']);

    $own = $this->actingAs($admin)
        ->fromRoute('admin.products.edit', $product)
        ->put(route('admin.products.update', $product), [
            'name' => 'Mine',
            'sku' => 'MINE-01',
            'price_cents' => 100,
            'currency' => 'USD',
        ]);

    $own->assertRedirectToRoute('admin.products.index')->assertSessionDoesntHaveErrors();

    $duplicate = $this->actingAs($admin)
        ->fromRoute('admin.products.edit', $product)
        ->put(route('admin.products.update', $product), [
            'name' => 'Mine',
            'sku' => 'OTHER-01',
            'price_cents' => 100,
            'currency' => 'USD',
        ]);

    $duplicate->assertRedirectToRoute('admin.products.edit', $product)
        ->assertSessionHasErrors(['sku']);
});

it('rejects a malformed sku or currency with a validation error', function (string $route, string $field, string $value): void {
    $admin = adminUser();
    $product = Product::factory()->create(['sku' => 'MINE-01']);

    $payload = ['name' => 'Widget', 'sku' => 'WIDGET-01', 'price_cents' => 100, 'currency' => 'USD', $field => $value];

    $response = $route === 'store'
        ? $this->actingAs($admin)->post(route('admin.products.store'), $payload)
        : $this->actingAs($admin)->put(route('admin.products.update', $product), $payload);

    $response->assertSessionHasErrors([$field]);
})->with(['store', 'update'])->with([
    'short sku' => ['sku', 'ab'],
    'underscore sku' => ['sku', 'a_b-1'],
    'spaced sku' => ['sku', 'ab c'],
    'numeric currency' => ['currency', '12$'],
]);

it('rejects a sku that differs from an existing one only by case', function (): void {
    $admin = adminUser();
    Product::factory()->create(['sku' => 'ABC-123']);

    $response = $this->actingAs($admin)->post(route('admin.products.store'), [
        'name' => 'Widget',
        'sku' => ' abc-123 ',
        'price_cents' => 100,
        'currency' => 'usd',
    ]);

    $response->assertSessionHasErrors(['sku']);

    expect(Product::query()->count())->toBe(1);
});

it('deletes a product and redirects with a success flash', function (): void {
    $admin = adminUser();
    $product = Product::factory()->create();

    $response = $this->actingAs($admin)->delete(route('admin.products.destroy', $product));

    $response->assertRedirectToRoute('admin.products.index')
        ->assertToast('Product deleted.');

    expect(Product::query()->whereKey($product->id)->exists())->toBeFalse();
});

it('forbids admins without the matching permission', function (string $permission, string $method, string $routeName): void {
    $admin = adminWithout($permission);
    $product = Product::factory()->create();

    $this->actingAs($admin)->{$method}(route($routeName, $product))->assertForbidden();
})->with([
    'create' => ['products.create', 'get', 'admin.products.create'],
    'update' => ['products.update', 'get', 'admin.products.edit'],
    'delete' => ['products.delete', 'delete', 'admin.products.destroy'],
]);
