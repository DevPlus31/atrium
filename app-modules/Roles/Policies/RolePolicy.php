<?php

declare(strict_types=1);

namespace Modules\Roles\Policies;

use App\Models\User;
use Modules\Roles\Domain\ValueObjects\RoleName;
use Spatie\Permission\Models\Role;

final readonly class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('roles.view');
    }

    public function create(User $user): bool
    {
        return $user->can('roles.create');
    }

    /**
     * System roles define panel access and the Gate bypass, so only a
     * super-admin may change them.
     */
    public function update(User $user, Role $role): bool
    {
        return $user->can('roles.update')
            && (! new RoleName($role->name)->isSystem() || $user->isSuperAdmin());
    }

    public function delete(User $user, Role $role): bool
    {
        return $user->can('roles.delete') && ! new RoleName($role->name)->isSystem();
    }
}
