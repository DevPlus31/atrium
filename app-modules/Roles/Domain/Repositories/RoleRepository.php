<?php

declare(strict_types=1);

namespace Modules\Roles\Domain\Repositories;

use App\Domain\Contracts\Repository;
use Spatie\Permission\Models\Role;

interface RoleRepository extends Repository
{
    /**
     * Re-read the role inside the current transaction, locking its row.
     */
    public function lockForUpdate(Role $role): Role;

    public function create(string $name): Role;

    public function rename(Role $role, string $name): void;

    /**
     * @param  list<string>  $permissions
     */
    public function syncPermissions(Role $role, array $permissions): void;

    public function delete(Role $role): void;
}
