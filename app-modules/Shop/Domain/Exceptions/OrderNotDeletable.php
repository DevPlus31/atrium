<?php

declare(strict_types=1);

namespace Modules\Shop\Domain\Exceptions;

use App\Domain\Exceptions\DomainException;

final class OrderNotDeletable extends DomainException
{
    public static function withNumber(string $number): self
    {
        return new self(sprintf('Order [%s] is kept for the books; only pending or cancelled orders can be deleted.', $number));
    }
}
