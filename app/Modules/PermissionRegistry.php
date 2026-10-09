<?php

declare(strict_types=1);

namespace App\Modules;

use InvalidArgumentException;

final class PermissionRegistry
{
    private const string NAME_PATTERN = '/^[a-z0-9-]+(\.[a-z0-9-]+)+$/';

    /**
     * @var array<string, list<string>>
     */
    private array $declarations = [];

    /**
     * Declare a permission and the roles it is assigned to by default.
     *
     * @param  list<string>  $roles
     */
    public function declare(string $permission, array $roles = []): void
    {
        // Spatie answers the Gate for any ability matching a permission name,
        // ahead of policies: an un-namespaced permission such as "update"
        // would approve every policy's update(). Names are "<area>.<action>".
        if (preg_match(self::NAME_PATTERN, $permission) !== 1) {
            throw new InvalidArgumentException(sprintf('Permission [%s] must be namespaced like "orders.view" (lowercase, dot-separated).', $permission));
        }

        $this->declarations[$permission] = array_values(array_unique([
            ...$this->declarations[$permission] ?? [],
            ...$roles,
        ]));
    }

    /**
     * @return list<string>
     */
    public function permissions(): array
    {
        return array_keys($this->declarations);
    }

    /**
     * Whether the given ability is a declared permission.
     */
    public function has(string $ability): bool
    {
        return array_key_exists($ability, $this->declarations);
    }

    /**
     * The declared permissions keyed by name, each with its default roles.
     *
     * @return array<string, list<string>>
     */
    public function roleAssignments(): array
    {
        return $this->declarations;
    }
}
