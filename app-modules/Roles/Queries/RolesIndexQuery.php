<?php

declare(strict_types=1);

namespace Modules\Roles\Queries;

use App\Modules\IndexQuery;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Role;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * @extends IndexQuery<Role>
 */
final readonly class RolesIndexQuery extends IndexQuery
{
    /**
     * The filtered and sorted roles index query, without pagination.
     *
     * @return QueryBuilder<Role>
     */
    public function query(): QueryBuilder
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
     * @param  Builder<Role>  $query
     */
    private function search(Builder $query, mixed $value): void
    {
        $search = implode(',', $this->stringValues($value));

        $query->whereLike('name', '%'.$search.'%');
    }
}
