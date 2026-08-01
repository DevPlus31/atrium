<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

/**
 * A value object is an immutable type defined entirely by its attributes; two
 * instances with equal attributes are interchangeable. Implementations are
 * `final readonly` and never appear in Inertia DTOs (which carry scalars only).
 */
interface ValueObject
{
    public function equals(self $other): bool;
}
