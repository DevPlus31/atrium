<?php

declare(strict_types=1);

namespace Modules\Shop\Actions;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Shop\Infrastructure\Models\Order;

final readonly class DeleteOrders
{
    public function __construct(private DeleteOrder $deleteOrder)
    {
        //
    }

    /**
     * Delete each of the orders, all or none, exactly as one by one.
     *
     * @param  Collection<int, Order>  $orders
     */
    public function handle(Collection $orders): void
    {
        DB::transaction(function () use ($orders): void {
            foreach ($orders as $order) {
                $this->deleteOrder->handle($order);
            }
        });
    }
}
