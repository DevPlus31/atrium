<?php

declare(strict_types=1);

namespace Modules\Users\Domain\Events;

use App\Domain\Contracts\DomainEvent;

final readonly class InvitationAccepted implements DomainEvent
{
    public function __construct(
        public string $invitationId,
        public ?string $inviterId,
        public string $userId,
        public string $userName,
    ) {
        //
    }
}
