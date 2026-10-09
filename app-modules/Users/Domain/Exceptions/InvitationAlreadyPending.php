<?php

declare(strict_types=1);

namespace Modules\Users\Domain\Exceptions;

use App\Domain\Exceptions\DomainException;

final class InvitationAlreadyPending extends DomainException
{
    public static function forEmail(string $email): self
    {
        return new self(sprintf('[%s] already has a pending invitation.', $email));
    }
}
