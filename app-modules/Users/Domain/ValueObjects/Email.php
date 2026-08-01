<?php

declare(strict_types=1);

namespace Modules\Users\Domain\ValueObjects;

use App\Domain\Contracts\ValueObject;
use Modules\Users\Domain\Exceptions\InvalidEmailException;
use Stringable;

final readonly class Email implements Stringable, ValueObject
{
    public string $value;

    public function __construct(string $value)
    {
        $normalized = mb_strtolower(mb_trim($value));

        if (filter_var($normalized, FILTER_VALIDATE_EMAIL) === false) {
            throw InvalidEmailException::forValue($value);
        }

        $this->value = $normalized;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function equals(ValueObject $other): bool
    {
        return $other instanceof self && $this->value === $other->value;
    }
}
