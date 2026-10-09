<?php

declare(strict_types=1);

namespace Modules\Roles\Actions;

use App\Modules\AuditLog;
use Illuminate\Support\Facades\DB;
use Modules\Roles\Domain\Exceptions\SystemRoleProtected;
use Modules\Roles\Domain\Repositories\RoleRepository;
use Modules\Roles\Domain\ValueObjects\RoleName;
use Spatie\Permission\Models\Role;

final readonly class UpdateRole
{
    public function __construct(private RoleRepository $roles)
    {
        //
    }

    /**
     * Rename the role and set its permissions; a system role keeps its name.
     *
     * @param  list<string>  $permissions
     */
    public function handle(Role $role, string $name, array $permissions): Role
    {
        $name = new RoleName($name);

        return DB::transaction(function () use ($role, $name, $permissions): Role {
            $role = $this->roles->lockForUpdate($role);
            $current = new RoleName($role->name);

            if (! $current->canBecome($name)) {
                throw SystemRoleProtected::cannotRename($current);
            }

            $old = [
                'name' => $role->name,
                'permissions' => $role->permissions()->pluck('name')->sort()->values()->all(),
            ];

            $this->roles->rename($role, (string) $name);
            $this->roles->syncPermissions($role, $permissions);

            AuditLog::record(
                log: 'roles',
                event: 'updated',
                subject: $role,
                properties: [
                    'old' => $old,
                    'attributes' => ['name' => (string) $name, 'permissions' => $permissions],
                ],
            );

            return $role->refresh();
        });
    }
}
