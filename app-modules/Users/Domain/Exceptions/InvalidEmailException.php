<?php

declare(strict_types=1);

namespace Modules\Users\Domain\Exceptions;

use App\Domain\Exceptions\DomainException;

final class InvalidEmailException extends DomainException
{
    public static function forValue(string $value): self
    {
        return new self(sprintf('The value [%s] is not a valid email address.', $value));
    }
}
