<?php

declare(strict_types=1);

namespace Modules\Roles\Infrastructure\Repositories;

use Modules\Roles\Domain\Repositories\RoleRepository;
use Spatie\Permission\Models\Role;

final readonly class EloquentRoleRepository implements RoleRepository
{
    public function lockForUpdate(Role $role): Role
    {
        return Role::query()->whereKey($role->getKey())->lockForUpdate()->firstOrFail();
    }

    public function create(string $name): Role
    {
        return Role::query()->create(['name' => $name]);
    }

    public function rename(Role $role, string $name): void
    {
        $role->update(['name' => $name]);
    }

    /**
     * @param  list<string>  $permissions
     */
    public function syncPermissions(Role $role, array $permissions): void
    {
        $role->syncPermissions($permissions);
    }

    public function delete(Role $role): void
    {
        $role->delete();
    }
}
