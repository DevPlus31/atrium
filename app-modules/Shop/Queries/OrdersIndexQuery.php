<?php

declare(strict_types=1);

namespace Modules\Shop\Queries;

use App\Modules\IndexQuery;
use Illuminate\Database\Eloquent\Builder;
use Modules\Shop\Infrastructure\Models\Order;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * @extends IndexQuery<Order>
 */
final readonly class OrdersIndexQuery extends IndexQuery
{
    /**
     * @return QueryBuilder<Order>
     */
    public function query(): QueryBuilder
    {
        return QueryBuilder::for(Order::class, $this->request)
            ->allowedFilters(
                AllowedFilter::callback('search', $this->search(...)),
                AllowedFilter::callback('status', $this->status(...)),
            )
            ->allowedSorts('number', 'customer_email', 'status', 'total_cents', 'created_at')
            ->defaultSort('-created_at');
    }

    /**
     * @param  Builder<Order>  $query
     */
    private function search(Builder $query, mixed $value): void
    {
        $search = implode(',', $this->stringValues($value));

        $query->where(function (Builder $query) use ($search): void {
            $query
                ->whereLike('number', '%'.$search.'%')
                ->orWhereLike('customer_email', '%'.$search.'%');
        });
    }

    /**
     * @param  Builder<Order>  $query
     */
    private function status(Builder $query, mixed $value): void
    {
        $query->whereIn('status', $this->stringValues(explode(',', implode(',', $this->stringValues($value)))));
    }
}
