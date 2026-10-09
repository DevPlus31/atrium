<?php

declare(strict_types=1);

namespace Modules\Dashboard\Data;

use Spatie\LaravelData\Data;

final class AccountOverviewWidgetData extends Data
{
    public function __construct(
        public bool $email_verified,
        public bool $two_factor_enabled,
        public int $passkeys,
        public string $member_since,
    ) {
        //
    }
}
