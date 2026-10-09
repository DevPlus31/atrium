<?php

declare(strict_types=1);

use App\Models\User;
use Laravel\Pennant\Feature;
use Modules\Shop\Infrastructure\Models\Order;

beforeEach(function (): void {
    $this->artisan('admin:sync-permissions')->assertSuccessful();
});

it("lists orders with the admin list's filters and pages", function (): void {
    $token = adminUser()->createToken('Reporting', ['orders.view'])->plainTextToken;
    $paid = Order::factory()->paid()->create(['customer_email' => 'ada@shop.example']);
    Order::factory()->create(['customer_email' => 'grace@shop.example']);

    $this->withToken($token)
        ->getJson(route('api.v1.orders.index', ['filter' => ['status' => 'paid']]))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $paid->id)
        ->assertJsonPath('data.0.number', $paid->number)
        ->assertJsonPath('data.0.status', 'paid')
        ->assertJsonPath('data.0.customer_email', 'ada@shop.example')
        ->assertJsonPath('meta.total', 1);
});

it('shows one order', function (): void {
    $token = adminUser()->createToken('Reporting', ['orders.view'])->plainTextToken;
    $order = Order::factory()->shipped()->create();

    $this->withToken($token)
        ->getJson(route('api.v1.orders.show', $order))
        ->assertOk()
        ->assertJsonPath('data.number', $order->number)
        ->assertJsonPath('data.shipped_at', $order->shipped_at?->toIso8601String());
});

it('needs a token that carries orders.view', function (): void {
    $order = Order::factory()->create();
    $token = adminUser()->createToken('Profile only', [])->plainTextToken;

    $this->withToken($token)->getJson(route('api.v1.orders.index'))->assertForbidden();
    $this->withToken($token)->getJson(route('api.v1.orders.show', $order))->assertForbidden();
});

it("limits even a super-admin to the token's permissions", function (): void {
    $token = superAdminUser()->createToken('Profile only', ['users.view'])->plainTextToken;

    $this->withToken($token)->getJson(route('api.v1.orders.index'))->assertForbidden();
});

it('never grants more than the user holds', function (): void {
    $token = User::factory()->create()->createToken('Forged', ['orders.view'])->plainTextToken;

    $this->withToken($token)->getJson(route('api.v1.orders.index'))->assertForbidden();
});

it('is gone while the Shop module is off', function (): void {
    $admin = adminUser();
    $token = $admin->createToken('Reporting', ['orders.view'])->plainTextToken;
    Feature::for($admin)->deactivate('module:shop');

    $this->withToken($token)->getJson(route('api.v1.orders.index'))->assertNotFound();
});

it('needs a token', function (): void {
    $order = Order::factory()->create();

    $this->getJson(route('api.v1.orders.index'))->assertUnauthorized();
    $this->getJson(route('api.v1.orders.show', $order))->assertUnauthorized();
});
