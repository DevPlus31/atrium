<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

/**
 * Marker interface for domain events: something that happened inside a module's
 * domain that the rest of the system may react to. Dispatched through the
 * framework event bus by the Action that owns the write.
 */
interface DomainEvent
{
    //
}
