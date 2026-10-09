<?php

declare(strict_types=1);

namespace App\Modules\Data;

use Spatie\LaravelData\Data;

/**
 * The impersonation state the shell banner needs while an admin acts as
 * another user.
 */
final class ImpersonationData extends Data
{
    public function __construct(
        public string $impersonator,
    ) {
        //
    }
}
