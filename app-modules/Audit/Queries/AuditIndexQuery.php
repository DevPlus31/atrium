<?php

declare(strict_types=1);

namespace Modules\Audit\Queries;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Spatie\Activitylog\Models\Activity;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

final readonly class AuditIndexQuery
{
    private const int DEFAULT_PER_PAGE = 15;

    private const int MAX_PER_PAGE = 100;

    public function __construct(private Request $request)
    {
        //
    }

    /**
     * The filtered and sorted audit log index query, without pagination.
     *
     * @return QueryBuilder<Activity>
     */
    public function builder(): QueryBuilder
    {
        $builder = QueryBuilder::for(Activity::class, $this->request)
            ->allowedFilters(
                AllowedFilter::callback('search', $this->search(...)),
                AllowedFilter::exact('log_name'),
                AllowedFilter::exact('event'),
            )
            ->allowedSorts('created_at')
            ->defaultSort('-created_at');

        $builder->getEloquentBuilder()->with(['causer', 'subject']);

        return $builder;
    }

    /**
     * @return LengthAwarePaginator<int, Activity>
     */
    public function paginate(): LengthAwarePaginator
    {
        return $this->builder()
            ->paginate($this->perPage())
            ->appends($this->request->query());
    }

    /**
     * @param  Builder<Activity>  $query
     */
    private function search(Builder $query, mixed $value): void
    {
        $search = implode(',', $this->stringValues($value));

        $query->where(function (Builder $query) use ($search): void {
            $query
                ->where('description', 'like', '%'.$search.'%')
                ->orWhere('log_name', 'like', '%'.$search.'%')
                ->orWhere('event', 'like', '%'.$search.'%')
                ->orWhereHasMorph('causer', [User::class], function (Builder $query) use ($search): void {
                    $query
                        ->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%');
                });
        });
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
