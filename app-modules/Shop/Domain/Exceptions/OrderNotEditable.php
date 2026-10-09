<?php

declare(strict_types=1);

namespace Modules\Shop\Domain\Exceptions;

use App\Domain\Exceptions\DomainException;

final class OrderNotEditable extends DomainException
{
    public static function withNumber(string $number): self
    {
        return new self(sprintf('Order [%s] can only be edited while pending.', $number));
    }
}
