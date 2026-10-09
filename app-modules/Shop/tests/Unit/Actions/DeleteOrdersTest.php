<?php

declare(strict_types=1);

use Modules\Shop\Actions\DeleteOrders;
use Modules\Shop\Domain\Exceptions\OrderNotDeletable;
use Modules\Shop\Infrastructure\Models\Order;

it('deletes every given order', function (): void {
    $orders = Order::factory()->count(2)->create();

    resolve(DeleteOrders::class)->handle($orders);

    expect(Order::query()->exists())->toBeFalse();
});

it('deletes none when one of them must be kept', function (): void {
    $pending = Order::factory()->create();
    $paid = Order::factory()->paid()->create();

    expect(fn () => resolve(DeleteOrders::class)->handle(Order::query()->whereKey([$pending->id, $paid->id])->oldest()->get()))
        ->toThrow(OrderNotDeletable::class);

    expect(Order::query()->count())->toBe(2);
});
