<?php

declare(strict_types=1);

namespace Modules\Shop\Domain\ValueObjects;

use App\Domain\Contracts\ValueObject;
use Illuminate\Support\Str;
use Modules\Shop\Domain\Exceptions\InvalidOrderNumber;
use Stringable;

/**
 * Human-facing order reference: `ORD-<yymmdd>-<6 alphanumerics>`.
 */
final readonly class OrderNumber implements Stringable, ValueObject
{
    public const string PATTERN = '/^ORD-\d{6}-[A-Z0-9]{6}$/';

    public string $value;

    public function __construct(string $value)
    {
        $normalized = mb_strtoupper(mb_trim($value));

        if (in_array(preg_match(self::PATTERN, $normalized), [0, false], true)) {
            throw InvalidOrderNumber::forValue($value);
        }

        $this->value = $normalized;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public static function generate(): self
    {
        return new self('ORD-'.now()->format('ymd').'-'.Str::upper(Str::random(6)));
    }

    public function equals(ValueObject $other): bool
    {
        return $other instanceof self && $other->value === $this->value;
    }
}
