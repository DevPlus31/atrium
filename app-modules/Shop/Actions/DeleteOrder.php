<?php

declare(strict_types=1);

namespace Modules\Shop\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Shop\Domain\Repositories\OrderRepository;
use Modules\Shop\Infrastructure\Models\Order;

final readonly class DeleteOrder
{
    public function __construct(private OrderRepository $orders)
    {
        //
    }

    public function handle(Order $order): void
    {
        DB::transaction(function () use ($order): void {
            $order = $this->orders->lockForUpdate($order);

            $order->ensureDeletable();

            $this->orders->delete($order);

            activity('shop')
                ->performedOn($order)
                ->event('deleted')
                ->withProperties([
                    'attributes' => ['number' => $order->number],
                ])
                ->log('deleted');
        });
    }
}
