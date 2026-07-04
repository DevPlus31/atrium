<?php

declare(strict_types=1);

namespace Modules\Users\Data;

use Spatie\LaravelData\Data;

final class RecentUsersWidgetData extends Data
{
    /**
     * @param  list<array{id: string, name: string, email: string, created_at: string}>  $users
     */
    public function __construct(
        public array $users,
    ) {
        //
    }
}
