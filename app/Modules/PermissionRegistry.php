<?php

declare(strict_types=1);

namespace App\Modules;

final class PermissionRegistry
{
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
     * The declared permissions keyed by name, each with its default roles.
     *
     * @return array<string, list<string>>
     */
    public function roleAssignments(): array
    {
        return $this->declarations;
    }
}
