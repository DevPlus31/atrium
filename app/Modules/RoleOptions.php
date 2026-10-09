<?php

declare(strict_types=1);

namespace App\Modules;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * The role and permission names forms offer as choices, sorted by name.
 */
final readonly class RoleOptions
{
    /**
     * @return list<string>
     */
    public static function roleNames(): array
    {
        /** @var list<string> $names */
        $names = Role::query()->orderBy('name')->pluck('name')->all();

        return $names;
    }

    /**
     * @return list<string>
     */
    public static function permissionNames(): array
    {
        /** @var list<string> $names */
        $names = Permission::query()->orderBy('name')->pluck('name')->all();

        return $names;
    }
}
