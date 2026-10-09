<?php

declare(strict_types=1);

namespace Modules\Shop\Domain\Exceptions;

use App\Domain\Exceptions\DomainException;

final class InvalidOrderNumber extends DomainException
{
    public static function forValue(string $value): self
    {
        return new self(sprintf('[%s] is not a valid order number.', $value));
    }
}
