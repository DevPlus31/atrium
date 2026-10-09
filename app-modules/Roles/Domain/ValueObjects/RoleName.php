<?php

declare(strict_types=1);

namespace Modules\Roles\Domain\ValueObjects;

use App\Domain\Contracts\ValueObject;
use App\Models\User;
use Modules\Roles\Domain\Exceptions\InvalidRoleName;
use Stringable;

/**
 * A role's name. The system roles ship with the app (panel entry and the
 * super-admin) and can never be renamed or deleted.
 */
final readonly class RoleName implements Stringable, ValueObject
{
    /**
     * @var list<string>
     */
    public const array SYSTEM = [User::PANEL_ROLE, User::SUPER_ADMIN_ROLE];

    public string $value;

    public function __construct(string $value)
    {
        $trimmed = mb_trim($value);

        if ($trimmed === '') {
            throw InvalidRoleName::empty();
        }

        $this->value = $trimmed;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function isSystem(): bool
    {
        return in_array($this->value, self::SYSTEM, true);
    }

    /**
     * Whether a role with this name may be renamed to the other: a system
     * role keeps its name.
     */
    public function canBecome(self $other): bool
    {
        return ! $this->isSystem() || $this->equals($other);
    }

    public function equals(ValueObject $other): bool
    {
        return $other instanceof self && $this->value === $other->value;
    }
}
