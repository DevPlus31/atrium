<?php

declare(strict_types=1);

namespace Modules\Shop\Listeners;

use App\Models\User;
use Modules\Shop\Domain\Enums\OrderStatus;
use Modules\Shop\Domain\Events\OrderStatusChanged;
use Modules\Shop\Infrastructure\Models\Order;
use Modules\Shop\Notifications\OrderStatusChangedNotification;

/**
 * Tells the customer, when they have a verified account with the order's
 * email, that their order moved on. Found by event discovery.
 */
final readonly class NotifyCustomerOfOrderStatus
{
    public function handle(OrderStatusChanged $event): void
    {
        $order = Order::query()->find($event->orderId);

        if (! $order instanceof Order) {
            return;
        }

        $customer = User::query()
            ->where('email', $order->customer_email)
            ->whereNotNull('email_verified_at')
            ->first();

        $customer?->notify(new OrderStatusChangedNotification($order->number, OrderStatus::from($event->to)));
    }
}
