<?php

declare(strict_types=1);

namespace Modules\Catalog\Queries;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Modules\Catalog\Infrastructure\Models\Product;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

final readonly class ProductsIndexQuery
{
    private const int DEFAULT_PER_PAGE = 15;

    private const int MAX_PER_PAGE = 100;

    public function __construct(private Request $request)
    {
        //
    }

    /**
     * The filtered and sorted products index query, without pagination.
     *
     * @return QueryBuilder<Product>
     */
    public function builder(): QueryBuilder
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
     * @return LengthAwarePaginator<int, Product>
     */
    public function paginate(): LengthAwarePaginator
    {
        return $this->builder()
            ->paginate($this->perPage())
            ->appends($this->request->query());
    }

    /**
     * @param  Builder<Product>  $query
     */
    private function search(Builder $query, mixed $value): void
    {
        $search = implode(',', $this->stringValues($value));

        $query->where(function (Builder $query) use ($search): void {
            $query
                ->where('name', 'like', '%'.$search.'%')
                ->orWhere('sku', 'like', '%'.$search.'%');
        });
    }

    /**
     * @param  Builder<Product>  $query
     */
    private function status(Builder $query, mixed $value): void
    {
        if ($value === 'published') {
            $query->whereNotNull('published_at');
        }

        if ($value === 'draft') {
            $query->whereNull('published_at');
        }
    }

    /**
     * @return list<string>
     */
    private function stringValues(mixed $value): array
    {
        $values = [];

        foreach (Arr::wrap($value) as $entry) {
            if (is_scalar($entry)) {
                $values[] = (string) $entry;
            }
        }

        return $values;
    }

    private function perPage(): int
    {
        $perPage = $this->request->integer('per_page', self::DEFAULT_PER_PAGE);

        return min(max($perPage, 1), self::MAX_PER_PAGE);
    }
}
