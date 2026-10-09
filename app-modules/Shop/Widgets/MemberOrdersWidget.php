<?php

declare(strict_types=1);

namespace Modules\Shop\Widgets;

use App\Models\User;
use Modules\Shop\Data\MemberOrdersWidgetData;
use Modules\Shop\Infrastructure\Models\Order;

final readonly class MemberOrdersWidget
{
    private const int LIMIT = 5;

    /**
     * The viewer's own orders, matched by their verified email address (the
     * member area requires one), newest first.
     */
    public function __invoke(User $user): MemberOrdersWidgetData
    {
        $query = Order::query()->where('customer_email', $user->email);

        $orders = (clone $query)
            ->latest()
            ->orderByDesc('number')
            ->limit(self::LIMIT)
            ->get();

        return new MemberOrdersWidgetData(
            total: $query->count(),
            orders: array_values($orders->map(static fn (Order $order): array => [
                'id' => $order->id,
                'number' => $order->number,
                'status' => $order->status,
                'total_cents' => $order->total_cents,
                'currency' => $order->currency,
                'placed_at' => $order->created_at->toIso8601String(),
            ])->all()),
        );
    }
}
