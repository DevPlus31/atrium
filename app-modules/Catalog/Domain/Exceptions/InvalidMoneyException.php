<?php

declare(strict_types=1);

namespace Modules\Catalog\Domain\Exceptions;

use App\Domain\Exceptions\DomainException;

final class InvalidMoneyException extends DomainException
{
    public static function negativeAmount(int $amount): self
    {
        return new self(sprintf('The amount [%d] cannot be negative.', $amount));
    }

    public static function invalidCurrency(string $currency): self
    {
        return new self(sprintf('The currency [%s] must be a three-letter ISO code.', $currency));
    }
}
