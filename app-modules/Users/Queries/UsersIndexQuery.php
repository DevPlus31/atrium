<?php

declare(strict_types=1);

namespace Modules\Users\Queries;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

final readonly class UsersIndexQuery
{
    private const int DEFAULT_PER_PAGE = 15;

    private const int MAX_PER_PAGE = 100;

    public function __construct(private Request $request)
    {
        //
    }

    /**
     * The filtered and sorted users index query, without pagination.
     *
     * @return QueryBuilder<User>
     */
    public function builder(): QueryBuilder
    {
        $builder = QueryBuilder::for(User::class, $this->request)
            ->allowedFilters(
                AllowedFilter::callback('search', $this->search(...)),
                AllowedFilter::callback('role', $this->role(...)),
                AllowedFilter::callback('verified', $this->verified(...)),
            )
            ->allowedSorts('name', 'email', 'created_at')
            ->defaultSort('-created_at');

        $builder->getEloquentBuilder()->with('roles');

        return $builder;
    }

    /**
     * @return LengthAwarePaginator<int, User>
     */
    public function paginate(): LengthAwarePaginator
    {
        return $this->builder()
            ->paginate($this->perPage())
            ->appends($this->request->query());
    }

    /**
     * @param  Builder<User>  $query
     */
    private function search(Builder $query, mixed $value): void
    {
        $search = implode(',', $this->stringValues($value));

        $query->where(function (Builder $query) use ($search): void {
            $query
                ->where('name', 'like', '%'.$search.'%')
                ->orWhere('email', 'like', '%'.$search.'%');
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
