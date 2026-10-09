<?php

declare(strict_types=1);

namespace Modules\Audit\Queries;

use App\Models\User;
use App\Modules\IndexQuery;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Models\Activity;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * @extends IndexQuery<Activity>
 */
final readonly class AuditIndexQuery extends IndexQuery
{
    /**
     * The filtered and sorted audit log index query, without pagination.
     *
     * @return QueryBuilder<Activity>
     */
    public function query(): QueryBuilder
    {
        $builder = QueryBuilder::for(Activity::class, $this->request)
            ->allowedFilters(
                AllowedFilter::callback('search', $this->search(...)),
                AllowedFilter::exact('log_name'),
                AllowedFilter::exact('event'),
            )
            ->allowedSorts('created_at')
            ->defaultSort('-created_at');

        $builder->getEloquentBuilder()->with('causer');

        return $builder;
    }

    /**
     * @param  Builder<Activity>  $query
     */
    private function search(Builder $query, mixed $value): void
    {
        $search = implode(',', $this->stringValues($value));

        $query->where(function (Builder $query) use ($search): void {
            $query
                ->whereLike('description', '%'.$search.'%')
                ->orWhereLike('log_name', '%'.$search.'%')
                ->orWhereLike('event', '%'.$search.'%')
                ->orWhereHasMorph('causer', [User::class], function (Builder $query) use ($search): void {
                    $query
                        ->whereLike('name', '%'.$search.'%')
                        ->orWhereLike('email', '%'.$search.'%');
                });
        });
    }
}
