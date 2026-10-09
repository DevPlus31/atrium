<?php

declare(strict_types=1);

namespace Modules\Audit\Data;

use Spatie\LaravelData\Data;

final class AccountActivityWidgetData extends Data
{
    /**
     * @param  list<array{id: string, event: string|null, causer: string|null, created_at: string}>  $entries
     */
    public function __construct(
        public array $entries,
    ) {
        //
    }
}
