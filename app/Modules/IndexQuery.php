<?php

declare(strict_types=1);

namespace App\Modules;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Shared pagination contract of every module index: `page`, `per_page`
 * (clamped to 1..100, default 15) and query-string preserving links.
 * Modules only describe their filters and sorts in query().
 *
 * @template TModel of Model
 */
abstract readonly class IndexQuery
{
    public const int MAX_PER_PAGE = 100;

    private const int DEFAULT_PER_PAGE = 15;

    public function __construct(protected Request $request)
    {
        //
    }

    /**
     * The module's allowed filters, sorts and default sort.
     *
     * @return QueryBuilder<TModel>
     */
    abstract public function query(): QueryBuilder;

    /**
     * The filtered and sorted index query, without pagination. Ties in the
     * requested sort are broken on the primary key, so offset pagination and
     * chunked exports never skip or repeat rows.
     *
     * @return QueryBuilder<TModel>
     */
    final public function builder(): QueryBuilder
    {
        $builder = $this->query();
        $eloquent = $builder->getEloquentBuilder();

        // Ties follow the primary sort's direction: under "newest first",
        // rows sharing a timestamp also list the newest (highest key) first.
        $primary = $eloquent->getQuery()->orders[0] ?? null;
        $direction = is_array($primary) && ($primary['direction'] ?? null) === 'desc' ? 'desc' : 'asc';

        $eloquent->orderBy($builder->getModel()->getQualifiedKeyName(), $direction);

        return $builder;
    }

    /**
     * @return LengthAwarePaginator<int, TModel>
     */
    final public function paginate(): LengthAwarePaginator
    {
        return $this->builder()
            ->paginate($this->perPage())
            ->appends($this->request->query());
    }

    /**
     * The scalar entries of a filter value, as strings.
     *
     * @return list<string>
     */
    protected function stringValues(mixed $value): array
    {
        $values = [];

        foreach (Arr::wrap($value) as $entry) {
            if (is_scalar($entry)) {
                $values[] = (string) $entry;
            }
        }

        return $values;
    }

    /**
     * Keep rows where any of the columns contains the search text. The query
     * builder splits filter values on commas; they are joined back here so
     * "Smith, Ada" is searched as typed.
     *
     * @template TSearched of Model
     *
     * @param  Builder<TSearched>  $query
     * @param  non-empty-list<string>  $columns
     * @return Builder<TSearched>
     */
    protected function whereLikeAny(Builder $query, array $columns, mixed $value): Builder
    {
        return TextSearch::whereLikeAny($query, $columns, implode(',', $this->stringValues($value)));
    }

    private function perPage(): int
    {
        $perPage = $this->request->integer('per_page', self::DEFAULT_PER_PAGE);

        return min(max($perPage, 1), self::MAX_PER_PAGE);
    }
}
