<?php

declare(strict_types=1);

namespace App\Domain\ValueObjects;

use App\Domain\Contracts\ValueObject;
use App\Domain\Exceptions\InvalidEmailException;
use Stringable;

/**
 * A sign-in or contact address, stored trimmed and lowercased. Shared by the
 * accounts (shell and Users) and Shop's customer emails.
 */
final readonly class Email implements Stringable, ValueObject
{
    public string $value;

    public function __construct(string $value)
    {
        $normalized = self::normalize($value);

        if (filter_var($normalized, FILTER_VALIDATE_EMAIL) === false) {
            throw InvalidEmailException::forValue($value);
        }

        $this->value = $normalized;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * The stored form of an address: trimmed and lowercased. Request
     * classes use it to clean input before validating it.
     */
    public static function normalize(string $value): string
    {
        return mb_strtolower(mb_trim($value));
    }

    public function equals(ValueObject $other): bool
    {
        return $other instanceof self && $this->value === $other->value;
    }
}
