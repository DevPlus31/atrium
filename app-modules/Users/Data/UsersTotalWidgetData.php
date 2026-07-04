<?php

declare(strict_types=1);

namespace Modules\Users\Data;

use Spatie\LaravelData\Data;

final class UsersTotalWidgetData extends Data
{
    /**
     * @param  list<array{date: string, count: int}>  $series
     */
    public function __construct(
        public int $total,
        public array $series,
    ) {
        //
    }
}
