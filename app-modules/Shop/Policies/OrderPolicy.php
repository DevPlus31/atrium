<?php

declare(strict_types=1);

namespace Modules\Shop\Policies;

use App\Models\User;
use Modules\Shop\Infrastructure\Models\Order;

final readonly class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('orders.view');
    }

    public function create(User $user): bool
    {
        return $user->can('orders.create');
    }

    /**
     * Customer and total are editable only while the order is pending.
     */
    public function update(User $user, Order $order): bool
    {
        return $user->can('orders.update') && $order->isEditable();
    }

    /**
     * Status changes need the update permission and a workflow move left.
     */
    public function transition(User $user, Order $order): bool
    {
        return $user->can('orders.update') && $order->status->transitions() !== [];
    }

    public function delete(User $user, Order $order): bool
    {
        return $user->can('orders.delete') && $order->isDeletable();
    }
}
