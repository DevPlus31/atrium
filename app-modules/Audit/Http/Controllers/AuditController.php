<?php

declare(strict_types=1);

namespace Modules\Audit\Http\Controllers;

use Illuminate\Routing\Attributes\Controllers\Authorize;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Audit\Data\ActivityData;
use Modules\Audit\Queries\AuditIndexQuery;
use Spatie\Activitylog\Models\Activity;
use Spatie\LaravelData\PaginatedDataCollection;

final readonly class AuditController
{
    #[Authorize('viewAny', Activity::class)]
    public function index(AuditIndexQuery $query): Response
    {
        return Inertia::render('audit::index', [
            'activities' => ActivityData::collect($query->paginate(), PaginatedDataCollection::class),
            'logNames' => $this->distinctValues('log_name'),
            'events' => $this->distinctValues('event'),
        ]);
    }

    /**
     * The distinct non-null values of the given column, for the index facets.
     *
     * @return list<string>
     */
    private function distinctValues(string $column): array
    {
        /** @var list<string> $values */
        $values = Activity::query()
            ->whereNotNull($column)
            ->distinct()
            ->orderBy($column)
            ->pluck($column)
            ->all();

        return $values;
    }
}
