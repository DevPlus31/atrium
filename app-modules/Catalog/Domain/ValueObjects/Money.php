<?php

declare(strict_types=1);

namespace Modules\Catalog\Domain\ValueObjects;

use App\Domain\Contracts\ValueObject;
use Modules\Catalog\Domain\Exceptions\InvalidMoneyException;

final readonly class Money implements ValueObject
{
    public string $currency;

    public function __construct(public int $amount, string $currency)
    {
        if ($amount < 0) {
            throw InvalidMoneyException::negativeAmount($amount);
        }

        $normalized = mb_strtoupper(mb_trim($currency));

        if (in_array(preg_match('/^[A-Z]{3}$/', $normalized), [0, false], true)) {
            throw InvalidMoneyException::invalidCurrency($currency);
        }

        $this->currency = $normalized;
    }

    public function equals(ValueObject $other): bool
    {
        return $other instanceof self
            && $this->amount === $other->amount
            && $this->currency === $other->currency;
    }
}
