<?php

declare(strict_types=1);

namespace Modules\Roles\Domain\Exceptions;

use App\Domain\Exceptions\DomainException;
use Modules\Roles\Domain\ValueObjects\RoleName;

final class SystemRoleProtected extends DomainException
{
    public static function cannotDelete(RoleName $name): self
    {
        return new self(sprintf('The [%s] role is a system role and cannot be deleted.', $name));
    }

    public static function cannotRename(RoleName $name): self
    {
        return new self(sprintf('The [%s] role is a system role and cannot be renamed.', $name));
    }
}
