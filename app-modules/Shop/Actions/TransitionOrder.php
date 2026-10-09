<?php

declare(strict_types=1);

namespace Modules\Shop\Actions;

use App\Modules\AuditLog;
use Illuminate\Support\Facades\DB;
use Modules\Shop\Domain\Enums\OrderStatus;
use Modules\Shop\Domain\Repositories\OrderRepository;
use Modules\Shop\Infrastructure\Models\Order;

final readonly class TransitionOrder
{
    public function __construct(private OrderRepository $orders)
    {
        //
    }

    public function handle(Order $order, OrderStatus $status): Order
    {
        $order = DB::transaction(function () use ($order, $status): Order {
            $order = $this->orders->lockForUpdate($order);
            $from = $order->status;

            $order->transitionTo($status);

            $this->orders->save($order);

            AuditLog::record(
                log: 'shop',
                event: 'status-changed',
                subject: $order,
                properties: [
                    'attributes' => ['from' => $from->value, 'to' => $status->value],
                ],
            );

            return $order;
        });

        $order->flushDomainEvents();

        return $order;
    }
}
