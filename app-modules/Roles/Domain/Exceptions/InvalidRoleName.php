<?php

declare(strict_types=1);

namespace Modules\Roles\Domain\Exceptions;

use App\Domain\Exceptions\DomainException;

final class InvalidRoleName extends DomainException
{
    public static function empty(): self
    {
        return new self('A role needs a name.');
    }
}
