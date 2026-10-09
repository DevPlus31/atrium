<?php

declare(strict_types=1);

namespace Modules\Users\Domain\Exceptions;

use App\Domain\Exceptions\DomainException;

final class InvitationNotPending extends DomainException
{
    public static function forEmail(string $email): self
    {
        return new self(sprintf('The invitation for [%s] was already used or has expired.', $email));
    }
}
