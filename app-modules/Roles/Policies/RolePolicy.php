<?php

declare(strict_types=1);

namespace Modules\Roles\Policies;

use App\Models\User;
use Spatie\Permission\Models\Role;

final readonly class RolePolicy
{
    /**
     * The role names that ship with the application and must never be deleted or renamed.
     *
     * @var list<string>
     */
    public const array SYSTEM_ROLES = ['admin', 'super-admin'];

    public static function isSystemRole(Role $role): bool
    {
        return in_array($role->name, self::SYSTEM_ROLES, true);
    }

    public function viewAny(User $user): bool
    {
        return $user->can('roles.view');
    }

    public function create(User $user): bool
    {
        return $user->can('roles.create');
    }

    public function update(User $user): bool
    {
        return $user->can('roles.update');
    }

    public function delete(User $user, Role $role): bool
    {
        return $user->can('roles.delete') && ! self::isSystemRole($role);
    }
}
