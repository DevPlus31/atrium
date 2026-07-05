<?php

declare(strict_types=1);

namespace Modules\Roles\Queries;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Spatie\Permission\Models\Role;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

final readonly class RolesIndexQuery
{
    private const int DEFAULT_PER_PAGE = 15;

    private const int MAX_PER_PAGE = 100;

    public function __construct(private Request $request)
    {
        //
    }

    /**
     * The filtered and sorted roles index query, without pagination.
     *
     * @return QueryBuilder<Role>
     */
    public function builder(): QueryBuilder
    {
        $builder = QueryBuilder::for(Role::class, $this->request)
            ->allowedFilters(
                AllowedFilter::callback('search', $this->search(...)),
            )
            ->allowedSorts('name', 'created_at')
            ->defaultSort('name');

        $builder->getEloquentBuilder()->with('permissions')->withCount('users');

        return $builder;
    }

    /**
     * @return LengthAwarePaginator<int, Role>
     */
    public function paginate(): LengthAwarePaginator
    {
        return $this->builder()
            ->paginate($this->perPage())
            ->appends($this->request->query());
    }

    /**
     * @param  Builder<Role>  $query
     */
    private function search(Builder $query, mixed $value): void
    {
        $search = implode(',', $this->stringValues($value));

        $query->where('name', 'like', '%'.$search.'%');
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
