<?php

declare(strict_types=1);

use App\Domain\Exceptions\InvalidMoneyException;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Modules\Shop\Actions\CreateOrder;
use Modules\Shop\Actions\DeleteOrder;
use Modules\Shop\Actions\TransitionOrder;
use Modules\Shop\Actions\UpdateOrder;
use Modules\Shop\Domain\Enums\OrderStatus;
use Modules\Shop\Domain\Events\OrderPlaced;
use Modules\Shop\Domain\Events\OrderStatusChanged;
use Modules\Shop\Domain\Exceptions\InvalidOrderTransition;
use Modules\Shop\Domain\Exceptions\OrderNotDeletable;
use Modules\Shop\Domain\Exceptions\OrderNotEditable;
use Modules\Shop\Domain\ValueObjects\OrderNumber;
use Modules\Shop\Infrastructure\Models\Order;
use Spatie\Activitylog\Models\Activity;

it('places an order, logs it and dispatches the event after commit', function (): void {
    Event::fake([OrderPlaced::class]);

    $order = resolve(CreateOrder::class)->handle('jane@example.com', 4200, 'usd');

    expect($order->exists)->toBeTrue()
        ->and($order->number)->toMatch(OrderNumber::PATTERN)
        ->and($order->status)->toBe(OrderStatus::Pending)
        ->and($order->currency)->toBe('USD')
        ->and(Activity::query()->where('log_name', 'shop')->where('event', 'created')->sole()->getProperty('attributes'))
        ->toBe(['number' => $order->number, 'customer_email' => 'jane@example.com', 'total_cents' => 4200, 'currency' => 'USD']);

    Event::assertDispatched(OrderPlaced::class, fn (OrderPlaced $event): bool => $event->orderId === $order->id);
});

it('rejects an invalid total before writing anything', function (): void {
    expect(fn () => resolve(CreateOrder::class)->handle('jane@example.com', -1, 'USD'))
        ->toThrow(InvalidMoneyException::class)
        ->and(Order::query()->count())->toBe(0);
});

it('updates a pending order', function (): void {
    $order = Order::factory()->create();

    resolve(UpdateOrder::class)->handle($order, 'new@example.com', 999, 'eur');

    expect($order->refresh()->customer_email)->toBe('new@example.com')
        ->and($order->total_cents)->toBe(999)
        ->and($order->currency)->toBe('EUR')
        ->and(Activity::query()->where('event', 'updated')->exists())->toBeTrue();
});

it('refuses to edit an order after payment', function (): void {
    $order = Order::factory()->paid()->create(['customer_email' => 'old@example.com']);

    expect(fn () => resolve(UpdateOrder::class)->handle($order, 'new@example.com', 1, 'USD'))
        ->toThrow(OrderNotEditable::class)
        ->and($order->refresh()->customer_email)->toBe('old@example.com');
});

it('moves an order along its workflow, logging and dispatching after commit', function (): void {
    Event::fake([OrderStatusChanged::class]);
    $order = Order::factory()->create();

    resolve(TransitionOrder::class)->handle($order, OrderStatus::Paid);

    expect($order->refresh()->status)->toBe(OrderStatus::Paid)
        ->and(Activity::query()->where('event', 'status-changed')->sole()->getProperty('attributes'))
        ->toBe(['from' => 'pending', 'to' => 'paid']);

    Event::assertDispatched(OrderStatusChanged::class, fn (OrderStatusChanged $event): bool => $event->to === 'paid');
});

it('rejects a transition outside the workflow without side effects', function (): void {
    Event::fake([OrderStatusChanged::class]);
    $order = Order::factory()->shipped()->create();

    expect(fn () => resolve(TransitionOrder::class)->handle($order, OrderStatus::Cancelled))
        ->toThrow(InvalidOrderTransition::class)
        ->and($order->refresh()->status)->toBe(OrderStatus::Shipped)
        ->and(Activity::query()->where('event', 'status-changed')->exists())->toBeFalse();

    Event::assertNotDispatched(OrderStatusChanged::class);
});

it('deletes an order and logs its number', function (): void {
    $order = Order::factory()->create();

    resolve(DeleteOrder::class)->handle($order);

    expect(Order::query()->count())->toBe(0)
        ->and(Activity::query()->where('event', 'deleted')->sole()->getProperty('attributes'))
        ->toBe(['number' => $order->number]);
});

it('refuses to delete a paid or shipped order', function (string $state): void {
    $order = Order::factory()->{$state}()->create();

    expect(fn () => resolve(DeleteOrder::class)->handle($order))
        ->toThrow(OrderNotDeletable::class)
        ->and(Order::query()->whereKey($order->getKey())->exists())->toBeTrue()
        ->and(Activity::query()->where('event', 'deleted')->exists())->toBeFalse();
})->with(['paid', 'shipped']);

it('checks the workflow against the stored order, not a stale copy', function (string $action): void {
    $stale = Order::factory()->create();
    Order::query()->whereKey($stale->getKey())->update(['status' => OrderStatus::Shipped->value, 'shipped_at' => now()]);

    $attempt = match ($action) {
        'transition' => fn () => resolve(TransitionOrder::class)->handle($stale, OrderStatus::Paid),
        'update' => fn () => resolve(UpdateOrder::class)->handle($stale, 'new@example.com', 1, 'USD'),
        'delete' => fn () => resolve(DeleteOrder::class)->handle($stale),
    };

    expect($attempt)->toThrow(match ($action) {
        'transition' => InvalidOrderTransition::class,
        'update' => OrderNotEditable::class,
        'delete' => OrderNotDeletable::class,
    });

    expect(Order::query()->whereKey($stale->getKey())->sole()->status)->toBe(OrderStatus::Shipped)
        ->and(Activity::query()->where('log_name', 'shop')->exists())->toBeFalse();
})->with(['transition', 'update', 'delete']);

it('rolls back and dispatches nothing when the audit log fails', function (string $action): void {
    Event::fake([OrderPlaced::class, OrderStatusChanged::class]);
    $order = Order::factory()->create(['customer_email' => 'kept@example.com']);

    Activity::creating(function (): void {
        throw new RuntimeException('Activity log unavailable.');
    });

    $attempt = match ($action) {
        'create' => fn () => resolve(CreateOrder::class)->handle('new@example.com', 100, 'USD'),
        'update' => fn () => resolve(UpdateOrder::class)->handle($order, 'changed@example.com', 1, 'USD'),
        'transition' => fn () => resolve(TransitionOrder::class)->handle($order, OrderStatus::Paid),
        'delete' => fn () => resolve(DeleteOrder::class)->handle($order),
    };

    expect($attempt)->toThrow(RuntimeException::class, 'Activity log unavailable.');

    $stored = Order::query()->sole();

    expect($stored->customer_email)->toBe('kept@example.com')
        ->and($stored->status)->toBe(OrderStatus::Pending);

    Event::assertNothingDispatched();
})->with(['create', 'update', 'transition', 'delete']);

it('retries with a fresh number when the generated one is taken', function (): void {
    Str::createRandomStringsUsingSequence(['TAKEN1', 'TAKEN1', 'FRESH2']);
    $existing = resolve(CreateOrder::class)->handle('first@example.com', 100, 'USD');

    $order = resolve(CreateOrder::class)->handle('second@example.com', 200, 'USD');

    expect($existing->number)->toEndWith('-TAKEN1')
        ->and($order->number)->toEndWith('-FRESH2')
        ->and(Order::query()->count())->toBe(2);
});

it('stores a revised customer email lowercased', function (): void {
    $order = Order::factory()->create();

    resolve(UpdateOrder::class)->handle($order, ' New@Example.COM ', 100, 'USD');

    expect($order->refresh()->customer_email)->toBe('new@example.com');
});
