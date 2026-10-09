<?php

declare(strict_types=1);

namespace App\Domain\Exceptions;

final class LastAdministrator extends DomainException
{
    public static function forRole(string $role): self
    {
        return new self(sprintf('The last holder of the [%s] role cannot delete their account.', $role));
    }
}
