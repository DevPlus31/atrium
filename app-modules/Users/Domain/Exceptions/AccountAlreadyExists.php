<?php

declare(strict_types=1);

namespace Modules\Users\Domain\Exceptions;

use App\Domain\Exceptions\DomainException;

final class AccountAlreadyExists extends DomainException
{
    public static function forEmail(string $email): self
    {
        return new self(sprintf('An account with the email [%s] already exists.', $email));
    }
}
