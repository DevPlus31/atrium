<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

/**
 * Marker interface implemented by every module's repository contract. It gives
 * the architecture tests a single hook to assert the repository convention
 * (a Domain interface with an `Eloquent*` implementation in Infrastructure).
 */
interface Repository
{
    //
}
