<?php

declare(strict_types=1);

namespace Modules\Users\Actions;

use App\Modules\AuditLog;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Modules\Users\Queries\UsersIndexQuery;

final readonly class RecordUsersExport
{
    private const int MAX_VALUE_LENGTH = 255;

    /**
     * Log the export with the filters it applied: only the keys the index
     * understands, as bounded strings, so a crafted URL cannot write
     * arbitrary data into the audit log.
     *
     * @param  array<array-key, mixed>  $filter
     */
    public function handle(array $filter): void
    {
        $applied = array_map(
            static fn (mixed $value): string => is_scalar($value) ? Str::limit((string) $value, self::MAX_VALUE_LENGTH, '') : '',
            Arr::only($filter, UsersIndexQuery::FILTERS),
        );

        AuditLog::record(
            log: 'users',
            event: 'exported',
            properties: ['filter' => $applied],
        );
    }
}
