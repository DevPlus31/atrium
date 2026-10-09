<?php

declare(strict_types=1);

namespace Modules\Shop\Infrastructure\Repositories;

use Modules\Shop\Domain\Repositories\OrderRepository;
use Modules\Shop\Infrastructure\Models\Order;

final readonly class EloquentOrderRepository implements OrderRepository
{
    public function lockForUpdate(Order $order): Order
    {
        return Order::query()->whereKey($order->getKey())->lockForUpdate()->firstOrFail();
    }

    public function save(Order $order): void
    {
        $order->save();
    }

    public function delete(Order $order): void
    {
        $order->delete();
    }
}
