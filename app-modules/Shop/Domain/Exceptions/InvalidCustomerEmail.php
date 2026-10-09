<?php

declare(strict_types=1);

namespace Modules\Shop\Domain\Exceptions;

use App\Domain\Exceptions\DomainException;

final class InvalidCustomerEmail extends DomainException
{
    public static function forValue(string $value): self
    {
        return new self(sprintf('The value [%s] is not a valid customer email address.', $value));
    }
}
