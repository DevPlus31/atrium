<?php

declare(strict_types=1);

use App\Actions\DeleteEach;
use Modules\Shop\Actions\DeleteOrder;
use Modules\Shop\Domain\Exceptions\OrderNotDeletable;
use Modules\Shop\Infrastructure\Models\Order;

it('deletes no order of a selection when one of them must be kept', function (): void {
    $pending = Order::factory()->create();
    $paid = Order::factory()->paid()->create();

    expect(fn () => resolve(DeleteEach::class)->handle(
        Order::query()->whereKey([$pending->id, $paid->id])->oldest()->get(),
        resolve(DeleteOrder::class)->handle(...),
    ))
        ->toThrow(OrderNotDeletable::class);

    expect(Order::query()->count())->toBe(2);
});
