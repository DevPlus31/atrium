<?php

declare(strict_types=1);

namespace Modules\Users\Domain\Events;

use App\Domain\Contracts\DomainEvent;

/**
 * An invitation was issued or renewed: its email should go out.
 */
final readonly class InvitationSent implements DomainEvent
{
    public function __construct(public string $invitationId)
    {
        //
    }
}
