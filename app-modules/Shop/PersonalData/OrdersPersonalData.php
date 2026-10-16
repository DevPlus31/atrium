<?php

declare(strict_types=1);

namespace Modules\Shop\PersonalData;

use App\Models\User;
use Modules\Shop\Infrastructure\Models\Order;

/**
 * The orders placed with the user's email address, for their data export.
 * There is no eraser: orders are accounting records the law obliges the
 * business to keep, so they outlive the account (say so in the privacy
 * policy).
 */
final readonly class OrdersPersonalData
{
    /**
     * @return list<array<string, mixed>>
     */
    public function __invoke(User $user): array
    {
        return array_values(Order::query()
            ->where('customer_email', $user->email)
            ->oldest()
            ->get()
            ->map(static fn (Order $order): array => [
                'number' => $order->number,
                'status' => $order->status->value,
                'total_cents' => $order->total_cents,
                'currency' => $order->currency,
                'paid_at' => $order->paid_at?->toIso8601String(),
                'shipped_at' => $order->shipped_at?->toIso8601String(),
                'cancelled_at' => $order->cancelled_at?->toIso8601String(),
                'created_at' => $order->created_at->toIso8601String(),
            ])
            ->all());
    }
}
