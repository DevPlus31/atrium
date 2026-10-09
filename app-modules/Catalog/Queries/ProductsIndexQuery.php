<?php

declare(strict_types=1);

namespace Modules\Catalog\Queries;

use App\Modules\IndexQuery;
use Illuminate\Database\Eloquent\Builder;
use Modules\Catalog\Infrastructure\Models\Product;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * @extends IndexQuery<Product>
 */
final readonly class ProductsIndexQuery extends IndexQuery
{
    /**
     * The filtered and sorted products index query, without pagination.
     *
     * @return QueryBuilder<Product>
     */
    public function query(): QueryBuilder
    {
        return QueryBuilder::for(Product::class, $this->request)
            ->allowedFilters(
                AllowedFilter::callback('search', $this->search(...)),
                AllowedFilter::callback('status', $this->status(...)),
            )
            ->allowedSorts('name', 'sku', 'price_cents', 'created_at')
            ->defaultSort('-created_at');
    }

    /**
     * @param  Builder<Product>  $query
     */
    private function search(Builder $query, mixed $value): void
    {
        $search = implode(',', $this->stringValues($value));

        $query->where(function (Builder $query) use ($search): void {
            $query
                ->whereLike('name', '%'.$search.'%')
                ->orWhereLike('sku', '%'.$search.'%');
        });
    }

    /**
     * @param  Builder<Product>  $query
     */
    private function status(Builder $query, mixed $value): void
    {
        $statuses = array_intersect($this->stringValues($value), ['draft', 'published']);

        // Both (or neither) selected: every product matches.
        if (count($statuses) !== 1) {
            return;
        }

        if (in_array('published', $statuses, true)) {
            $query->whereNotNull('published_at');

            return;
        }

        $query->whereNull('published_at');
    }
}
