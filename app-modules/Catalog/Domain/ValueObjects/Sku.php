<?php

declare(strict_types=1);

namespace Modules\Catalog\Domain\ValueObjects;

use App\Domain\Contracts\ValueObject;
use Modules\Catalog\Domain\Exceptions\InvalidSkuException;
use Stringable;

final readonly class Sku implements Stringable, ValueObject
{
    public string $value;

    public function __construct(string $value)
    {
        $normalized = mb_strtoupper(mb_trim($value));

        if (in_array(preg_match('/^[A-Z0-9][A-Z0-9-]{2,31}$/', $normalized), [0, false], true)) {
            throw InvalidSkuException::forValue($value);
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
