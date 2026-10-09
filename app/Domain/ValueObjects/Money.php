<?php

declare(strict_types=1);

namespace App\Domain\ValueObjects;

use App\Domain\Contracts\ValueObject;
use App\Domain\Exceptions\InvalidMoneyException;

final readonly class Money implements ValueObject
{
    public const string CURRENCY_PATTERN = '/^[A-Z]{3}$/';

    public string $currency;

    public function __construct(public int $amount, string $currency)
    {
        if ($amount < 0) {
            throw InvalidMoneyException::negativeAmount($amount);
        }

        $normalized = self::normalizeCurrency($currency);

        if (in_array(preg_match(self::CURRENCY_PATTERN, $normalized), [0, false], true)) {
            throw InvalidMoneyException::invalidCurrency($currency);
        }

        $this->currency = $normalized;
    }

    /**
     * The stored form of a currency code: trimmed and uppercased. Request
     * classes use it to clean input before validating it.
     */
    public static function normalizeCurrency(string $currency): string
    {
        return mb_strtoupper(mb_trim($currency));
    }

    /**
     * The validation rules of a currency-code field (after normalisation),
     * besides required/nullable: every form checks the same code shape.
     *
     * @return list<string>
     */
    public static function currencyRules(): array
    {
        return ['string', 'regex:'.self::CURRENCY_PATTERN];
    }

    public function equals(ValueObject $other): bool
    {
        return $other instanceof self
            && $this->amount === $other->amount
            && $this->currency === $other->currency;
    }
}
