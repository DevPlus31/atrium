<?php

declare(strict_types=1);

namespace Modules\Roles\Http\Requests\Concerns;

/**
 * Typed accessors for the validated role form, shared by create and update
 * (their rules differ: update protects system roles and held permissions).
 */
trait ProvidesRoleInput
{
    public function name(): string
    {
        /** @var string $name */
        $name = $this->validated('name');

        return $name;
    }

    /**
     * @return list<string>
     */
    public function permissions(): array
    {
        /** @var list<string> $permissions */
        $permissions = $this->validated('permissions', []);

        return $permissions;
    }
}
