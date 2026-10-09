<?php

declare(strict_types=1);

namespace Modules\Shop\Domain\Exceptions;

use App\Domain\Exceptions\DomainException;
use Modules\Shop\Domain\Enums\OrderStatus;

final class InvalidOrderTransition extends DomainException
{
    public static function between(string $number, OrderStatus $from, OrderStatus $to): self
    {
        return new self(sprintf('Order [%s] cannot move from %s to %s.', $number, $from->value, $to->value));
    }
}
