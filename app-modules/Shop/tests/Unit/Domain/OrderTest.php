<?php

declare(strict_types=1);

use App\Domain\ValueObjects\Money;
use Modules\Shop\Domain\Enums\OrderStatus;
use Modules\Shop\Domain\Events\OrderPlaced;
use Modules\Shop\Domain\Events\OrderStatusChanged;
use Modules\Shop\Domain\Exceptions\InvalidCustomerEmail;
use Modules\Shop\Domain\Exceptions\InvalidOrderTransition;
use Modules\Shop\Domain\Exceptions\OrderNotDeletable;
use Modules\Shop\Domain\Exceptions\OrderNotEditable;
use Modules\Shop\Domain\ValueObjects\OrderNumber;
use Modules\Shop\Infrastructure\Models\Order;

it('places a pending order and records the event', function (): void {
    $order = Order::place(new OrderNumber('ORD-261007-AB12CD'), 'jane@example.com', new Money(2500, 'eur'));

    expect($order->id)->toBeString()
        ->and($order->number)->toBe('ORD-261007-AB12CD')
        ->and($order->status)->toBe(OrderStatus::Pending)
        ->and($order->total_cents)->toBe(2500)
        ->and($order->currency)->toBe('EUR')
        ->and($order->isEditable())->toBeTrue()
        ->and($order->releaseDomainEvents())->toEqual([
            new OrderPlaced($order->id, 'ORD-261007-AB12CD', 2500, 'EUR'),
        ]);
});

it('moves through the workflow, stamping each step', function (): void {
    $order = Order::factory()->create();

    $order->transitionTo(OrderStatus::Paid);
    $order->transitionTo(OrderStatus::Shipped);

    expect($order->status)->toBe(OrderStatus::Shipped)
        ->and($order->paid_at?->toDateTimeString())->toBe(now()->toDateTimeString())
        ->and($order->shipped_at?->toDateTimeString())->toBe(now()->toDateTimeString())
        ->and($order->cancelled_at)->toBeNull()
        ->and($order->isEditable())->toBeFalse()
        ->and($order->releaseDomainEvents())->toEqual([
            new OrderStatusChanged($order->id, 'pending', 'paid'),
            new OrderStatusChanged($order->id, 'paid', 'shipped'),
        ]);
});

it('stamps cancellation', function (): void {
    $order = Order::factory()->create();

    $order->transitionTo(OrderStatus::Cancelled);

    expect($order->cancelled_at?->toDateTimeString())->toBe(now()->toDateTimeString());
});

it('refuses transitions outside the workflow', function (): void {
    $order = Order::factory()->shipped()->make();

    expect(fn () => $order->transitionTo(OrderStatus::Cancelled))
        ->toThrow(InvalidOrderTransition::class, 'cannot move from shipped to cancelled');
});

it('can only be revised while pending', function (): void {
    $order = Order::factory()->paid()->create();

    expect(fn () => $order->revise('ada@example.com', new Money(100, 'USD')))
        ->toThrow(OrderNotEditable::class, sprintf('Order [%s] can only be edited while pending.', $order->number));
});

it('refuses to be deleted once it must be kept', function (): void {
    Order::factory()->create()->ensureDeletable();
    Order::factory()->cancelled()->create()->ensureDeletable();

    expect(fn () => Order::factory()->paid()->create()->ensureDeletable())->toThrow(OrderNotDeletable::class);
});

it('refuses an invalid customer email whatever the entry point', function (): void {
    expect(fn (): Order => Order::place(OrderNumber::generate(), 'not an email', new Money(100, 'USD')))
        ->toThrow(InvalidCustomerEmail::class, 'The value [not an email] is not a valid customer email address.');
});
