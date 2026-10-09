<?php

declare(strict_types=1);

namespace Modules\Shop\Search;

use App\Models\User;
use App\Modules\Data\SearchResultData;
use App\Modules\TextSearch;
use Modules\Shop\Infrastructure\Models\Order;

/**
 * Finds orders by number or customer email for the command palette.
 * Orders the user cannot edit (no permission, or no longer pending) open
 * the filtered list instead.
 */
final readonly class OrdersSearch
{
    /**
     * @return list<SearchResultData>
     */
    public function __invoke(string $term, User $user, int $limit): array
    {
        $found = TextSearch::whereLikeAny(Order::query(), ['number', 'customer_email'], $term)
            ->latest()
            ->limit($limit)
            ->get();

        return array_values($found->map(fn (Order $order): SearchResultData => SearchResultData::forRecord(
            viewer: $user,
            record: $order,
            routes: 'admin.orders',
            title: $order->number,
            description: $order->customer_email,
            filter: $order->number,
        ))->all());
    }
}
