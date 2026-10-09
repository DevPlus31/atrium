<?php

declare(strict_types=1);

namespace Modules\Users\Queries;

use App\Models\User;
use App\Modules\IndexQuery;
use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * @extends IndexQuery<User>
 */
final readonly class UsersIndexQuery extends IndexQuery
{
    /**
     * The `filter[...]` keys the index understands.
     *
     * @var list<string>
     */
    public const array FILTERS = ['search', 'role', 'verified'];

    /**
     * The filtered and sorted users index query, without pagination.
     *
     * @return QueryBuilder<User>
     */
    public function query(): QueryBuilder
    {
        $builder = QueryBuilder::for(User::class, $this->request)
            ->allowedFilters(
                AllowedFilter::callback('search', $this->search(...)),
                AllowedFilter::callback('role', $this->role(...)),
                AllowedFilter::callback('verified', $this->verified(...)),
            )
            ->allowedSorts('name', 'email', 'created_at')
            ->defaultSort('-created_at');

        // Roles feed the index columns; direct permissions feed the per-row
        // impersonation ability check without an N+1.
        $builder->getEloquentBuilder()->with(['roles', 'permissions', 'media']);

        return $builder;
    }

    /**
     * @param  Builder<User>  $query
     */
    private function search(Builder $query, mixed $value): void
    {
        $search = implode(',', $this->stringValues($value));

        $query->where(function (Builder $query) use ($search): void {
            $query
                ->whereLike('name', '%'.$search.'%')
                ->orWhereLike('email', '%'.$search.'%');
        });
    }

    /**
     * @param  Builder<User>  $query
     */
    private function role(Builder $query, mixed $value): void
    {
        $roles = $this->stringValues($value);

        $query->whereHas('roles', function (Builder $query) use ($roles): void {
            $query->whereIn('name', $roles);
        });
    }

    /**
     * @param  Builder<User>  $query
     */
    private function verified(Builder $query, mixed $value): void
    {
        if ($value === 'yes') {
            $query->whereNotNull('email_verified_at');
        }

        if ($value === 'no') {
            $query->whereNull('email_verified_at');
        }
    }
}
