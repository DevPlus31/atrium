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
        $query->where(function (Builder $query) use ($value): void {
            $this->whereLikeAny($query, ['description', 'log_name', 'event'], $value)
                ->orWhereHasMorph('causer', [User::class], fn (Builder $causer): Builder => $this->whereLikeAny($causer, ['name', 'email'], $value));
        });
    }
}
