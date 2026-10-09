<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\NavRegistry;
use Inertia\Testing\AssertableInertia;
use Modules\Shop\Domain\Enums\OrderStatus;
use Modules\Shop\Domain\ValueObjects\OrderNumber;
use Modules\Shop\Infrastructure\Models\Order;

beforeEach(function (): void {
    $this->artisan('admin:sync-permissions')->assertSuccessful();
});

/**
 * @return array<string, mixed>
 */
function orderPayload(array $overrides = []): array
{
    return [
        'customer_email' => 'jane@example.com',
        'total_cents' => 4200,
        'currency' => 'USD',
        ...$overrides,
    ];
}

it('keeps guests and non-admins out', function (string $method, Closure $url): void {
    assertAdminOnly($method, $url());
})->with([
    'index' => ['get', fn (): string => route('admin.orders.index')],
    'create' => ['get', fn (): string => route('admin.orders.create')],
    'store' => ['post', fn (): string => route('admin.orders.store')],
    'edit' => ['get', fn (): string => route('admin.orders.edit', Order::factory()->create())],
    'update' => ['put', fn (): string => route('admin.orders.update', Order::factory()->create())],
    'destroy' => ['delete', fn (): string => route('admin.orders.destroy', Order::factory()->create())],
    'transition' => ['post', fn (): string => route('admin.orders.transition', Order::factory()->create())],
]);

it('forbids admins without the orders.view permission', function (): void {
    $this->actingAs(adminWithout('orders.view'))->get(route('admin.orders.index'))->assertForbidden();
});

it('registers the nav item for permitted admins and hides it otherwise', function (): void {
    $registry = $this->app->make(NavRegistry::class);

    $item = collect($registry->itemsFor(adminUser()))->firstWhere('label', 'Orders');

    expect($item)->not->toBeNull()
        ->and($item?->icon)->toBe('receipt')
        ->and($item?->group)->toBe('Shop')
        ->and(collect($registry->itemsFor(User::factory()->create()))->firstWhere('label', 'Orders'))->toBeNull();
});

it('renders the index with per-row abilities and allowed transitions', function (): void {
    $order = Order::factory()->create(['customer_email' => 'jane@example.com', 'total_cents' => 1250, 'currency' => 'EUR']);

    $response = $this->actingAs(adminUser())->get(route('admin.orders.index'));

    $response->assertOk()->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
        ->component('shop::index')
        ->has('orders.data', 1)
        ->where('orders.data.0.number', $order->number)
        ->where('orders.data.0.customer_email', 'jane@example.com')
        ->where('orders.data.0.status', 'pending')
        ->where('orders.data.0.total_cents', 1250)
        ->where('orders.data.0.currency', 'EUR')
        ->where('orders.data.0.can.update', true)
        ->where('orders.data.0.can.delete', true)
        ->where('orders.data.0.transitions', ['paid', 'cancelled'])
        ->where('statuses', ['pending', 'paid', 'shipped', 'cancelled']));
});

it('locks paid and shipped orders against editing and deletion', function (string $state, bool $deletable): void {
    Order::factory()->{$state}()->create();

    $this->actingAs(adminUser())->get(route('admin.orders.index'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->where('orders.data.0.can.update', false)
            ->where('orders.data.0.can.delete', $deletable));
})->with([
    'paid' => ['paid', false],
    'shipped' => ['shipped', false],
    'cancelled' => ['cancelled', true],
]);

it('searches by number or customer email and filters by status', function (): void {
    $admin = adminUser();
    $alpha = Order::factory()->create(['customer_email' => 'alpha@example.com']);
    Order::factory()->paid()->create(['customer_email' => 'beta@example.com']);

    $byEmail = $this->actingAs($admin)->get(route('admin.orders.index', ['filter' => ['search' => 'ALPHA@']]));
    $byNumber = $this->actingAs($admin)->get(route('admin.orders.index', ['filter' => ['search' => mb_strtolower(mb_substr($alpha->number, -6))]]));
    $byStatus = $this->actingAs($admin)->get(route('admin.orders.index', ['filter' => ['status' => 'paid']]));

    $byEmail->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page->has('orders.data', 1)->where('orders.data.0.customer_email', 'alpha@example.com'));
    $byNumber->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page->has('orders.data', 1)->where('orders.data.0.number', $alpha->number));
    $byStatus->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page->has('orders.data', 1)->where('orders.data.0.customer_email', 'beta@example.com'));
});

it('renders the create page', function (): void {
    $this->actingAs(adminUser())->get(route('admin.orders.create'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page->component('shop::create'));
});

it('places an order and redirects with a success flash', function (): void {
    $response = $this->actingAs(adminUser())
        ->fromRoute('admin.orders.create')
        ->post(route('admin.orders.store'), orderPayload(['customer_email' => ' Jane@Example.com ', 'currency' => 'eur']));

    $response->assertRedirectToRoute('admin.orders.index')
        ->assertToast('Order created.');

    $order = Order::query()->sole();

    expect($order->number)->toMatch(OrderNumber::PATTERN)
        ->and($order->customer_email)->toBe('jane@example.com')
        ->and($order->status)->toBe(OrderStatus::Pending)
        ->and($order->currency)->toBe('EUR');
});

it('validates the store request', function (array $payload, string $field): void {
    $this->actingAs(adminUser())
        ->fromRoute('admin.orders.create')
        ->post(route('admin.orders.store'), orderPayload($payload))
        ->assertRedirectToRoute('admin.orders.create')
        ->assertSessionHasErrors([$field]);

    expect(Order::query()->count())->toBe(0);
})->with([
    'missing email' => [['customer_email' => ''], 'customer_email'],
    'invalid email' => [['customer_email' => 'not-an-email'], 'customer_email'],
    'negative total' => [['total_cents' => -1], 'total_cents'],
    'bad currency' => [['currency' => '12$'], 'currency'],
]);

it('validates a single field precognitively without side effects', function (): void {
    $response = $this->actingAs(adminUser())
        ->withHeaders(['Precognition' => 'true', 'Precognition-Validate-Only' => 'customer_email'])
        ->postJson(route('admin.orders.store'), ['customer_email' => '']);

    $response->assertStatus(422)->assertJsonValidationErrors(['customer_email']);

    expect(Order::query()->count())->toBe(0);
});

it('renders the edit page for a pending order', function (): void {
    $order = Order::factory()->create();

    $this->actingAs(adminUser())->get(route('admin.orders.edit', $order))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('shop::edit')
            ->where('order.id', $order->id)
            ->where('order.number', $order->number));
});

it('updates a pending order and redirects with a success flash', function (): void {
    $order = Order::factory()->create();

    $response = $this->actingAs(adminUser())
        ->fromRoute('admin.orders.edit', $order)
        ->put(route('admin.orders.update', $order), orderPayload(['customer_email' => 'new@example.com', 'total_cents' => 999]));

    $response->assertRedirectToRoute('admin.orders.index')
        ->assertToast('Order updated.');

    expect($order->refresh()->customer_email)->toBe('new@example.com')
        ->and($order->total_cents)->toBe(999);
});

it('forbids editing an order after payment', function (): void {
    $order = Order::factory()->paid()->create();
    $admin = adminUser();

    $this->actingAs($admin)->get(route('admin.orders.edit', $order))->assertForbidden();
    $this->actingAs($admin)->put(route('admin.orders.update', $order), orderPayload())->assertForbidden();
});

it('deletes a pending order and redirects with a success flash', function (): void {
    $order = Order::factory()->create();

    $response = $this->actingAs(adminUser())->delete(route('admin.orders.destroy', $order));

    $response->assertRedirectToRoute('admin.orders.index')
        ->assertToast('Order deleted.');

    expect(Order::query()->whereKey($order->id)->exists())->toBeFalse();
});

it('forbids deleting a paid order', function (): void {
    $order = Order::factory()->paid()->create();

    $this->actingAs(adminUser())->delete(route('admin.orders.destroy', $order))->assertForbidden();

    expect(Order::query()->whereKey($order->id)->exists())->toBeTrue();
});

it('moves an order along every step of its workflow', function (string $from, string $to, string $stamp): void {
    $order = $from === 'pending' ? Order::factory()->create() : Order::factory()->{$from}()->create();

    $response = $this->actingAs(adminUser())
        ->fromRoute('admin.orders.index')
        ->post(route('admin.orders.transition', $order), ['status' => $to]);

    $response->assertRedirectToRoute('admin.orders.index')
        ->assertToast('Order '.$order->number.' marked as '.$to.'.');

    $order->refresh();

    expect($order->status)->toBe(OrderStatus::from($to))
        ->and($order->{$stamp}?->toIso8601String())->toBe(now()->toIso8601String());
})->with([
    'pay' => ['pending', 'paid', 'paid_at'],
    'cancel pending' => ['pending', 'cancelled', 'cancelled_at'],
    'ship' => ['paid', 'shipped', 'shipped_at'],
    'cancel paid' => ['paid', 'cancelled', 'cancelled_at'],
]);

it('rejects a transition outside the workflow as a validation error', function (string $status): void {
    $order = Order::factory()->paid()->create();

    $this->actingAs(adminUser())
        ->fromRoute('admin.orders.index')
        ->post(route('admin.orders.transition', $order), ['status' => $status])
        ->assertRedirectToRoute('admin.orders.index')
        ->assertSessionHasErrors(['status']);

    expect($order->refresh()->status)->toBe(OrderStatus::Paid);
})->with(['pending', 'paid', 'refunded']);

it('forbids changing the status of a finished order', function (string $state): void {
    $order = Order::factory()->{$state}()->create();

    $this->actingAs(adminUser())
        ->post(route('admin.orders.transition', $order), ['status' => 'cancelled'])
        ->assertForbidden();
})->with(['shipped', 'cancelled']);

it('forbids admins without the matching permission', function (string $permission, string $method, string $routeName, array $payload): void {
    $order = Order::factory()->create();

    $this->actingAs(adminWithout($permission))->{$method}(route($routeName, $order), $payload)->assertForbidden();
})->with([
    'create' => ['orders.create', 'get', 'admin.orders.create', []],
    'update' => ['orders.update', 'get', 'admin.orders.edit', []],
    'transition' => ['orders.update', 'post', 'admin.orders.transition', ['status' => 'paid']],
    'delete' => ['orders.delete', 'delete', 'admin.orders.destroy', []],
]);
