<?php

declare(strict_types=1);

namespace Modules\Shop\Search;

use App\Models\User;
use App\Modules\Data\SearchResultData;
use Illuminate\Database\Eloquent\Builder;
use Modules\Shop\Infrastructure\Models\Order;

/**
 * Finds orders by number or customer email for the command palette.
 * Paid and shipped orders cannot be edited, so they open the filtered list.
 */
final readonly class OrdersSearch
{
    /**
     * @return list<SearchResultData>
     */
    public function __invoke(string $term, User $user, int $limit): array
    {
        $found = Order::query()
            ->where(fn (Builder $query): Builder => $query
                ->whereLike('number', '%'.$term.'%')
                ->orWhereLike('customer_email', '%'.$term.'%'))
            ->latest()
            ->limit($limit)
            ->get();

        return array_values($found->map(fn (Order $order): SearchResultData => new SearchResultData(
            title: $order->number,
            description: $order->customer_email,
            url: $user->can('update', $order)
                ? route('admin.orders.edit', $order)
                : route('admin.orders.index', ['filter' => ['search' => $order->number]]),
        ))->all());
    }
}
