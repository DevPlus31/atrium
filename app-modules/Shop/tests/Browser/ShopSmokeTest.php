<?php

declare(strict_types=1);

use App\Models\User;
use Modules\Shop\Domain\Enums\OrderStatus;
use Modules\Shop\Infrastructure\Models\Order;

beforeEach(function (): void {
    $this->artisan('admin:sync-permissions')->assertSuccessful();
});

it('lists orders with their status and filters by status', function (): void {
    $this->actingAs(adminUser());

    Order::factory()->create(['customer_email' => 'pending@example.com', 'total_cents' => 1250, 'currency' => 'USD']);
    Order::factory()->shipped()->create(['customer_email' => 'shipped@example.com']);

    visit(route('admin.orders.index'))
        ->assertSee('pending@example.com')
        ->assertSee('$12.50')
        ->assertSee('Shipped')
        ->navigate(route('admin.orders.index', ['filter' => ['status' => 'shipped']]))
        ->assertSee('shipped@example.com')
        ->assertDontSee('pending@example.com')
        ->assertNoJavaScriptErrors();
});

it('places an order through the form', function (): void {
    $this->actingAs(adminUser());

    visit(route('admin.orders.create'))
        ->type('customer_email', 'new@example.com')
        ->type('total_cents', '4200')
        ->click('button[type="submit"]')
        ->assertPathIs('/admin/orders')
        ->assertSee('Order created.')
        ->assertSee('new@example.com')
        ->assertSee('Pending')
        ->assertNoJavaScriptErrors();

    expect(Order::query()->sole()->total_cents)->toBe(4200);
});

it('marks a pending order as paid', function (): void {
    $this->actingAs(adminUser());

    $order = Order::factory()->create(['customer_email' => 'flow@example.com']);

    visit(route('admin.orders.index'))
        ->click('tr:has-text("flow@example.com") [data-test="row-actions"]')
        ->click('Mark as paid')
        ->assertSee('Order '.$order->number.' marked as paid.')
        ->assertNoJavaScriptErrors();

    expect($order->refresh()->status)->toBe(OrderStatus::Paid);
});

it('cancels a paid order only after confirmation', function (): void {
    $this->actingAs(adminUser());

    $order = Order::factory()->paid()->create(['customer_email' => 'flow@example.com']);

    visit(route('admin.orders.index'))
        ->click('tr:has-text("flow@example.com") [data-test="row-actions"]')
        ->click('Cancel order')
        ->assertSee('Cancelled orders cannot be reopened.')
        ->click('Keep order')
        ->assertDontSee('Cancelled orders cannot be reopened.')
        ->click('tr:has-text("flow@example.com") [data-test="row-actions"]')
        ->click('Cancel order')
        ->click('[role="alertdialog"] button:has-text("Cancel order")')
        ->assertSee('Order '.$order->number.' marked as cancelled.')
        ->assertNoJavaScriptErrors();

    expect($order->refresh()->status)->toBe(OrderStatus::Cancelled);
});

it('shows a member their own orders on their home', function (): void {
    $this->actingAs(User::factory()->create(['email' => 'jane@example.com']));
    $order = Order::factory()->create(['customer_email' => 'jane@example.com']);

    visit(route('member.dashboard'))
        ->assertSee('Your orders')
        ->assertSee($order->number)
        ->assertNoJavaScriptErrors();
});
