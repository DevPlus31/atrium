<?php

declare(strict_types=1);

namespace Modules\Roles\Actions;

use App\Modules\AuditLog;
use Illuminate\Support\Facades\DB;
use Modules\Roles\Domain\Exceptions\SystemRoleProtected;
use Modules\Roles\Domain\Repositories\RoleRepository;
use Modules\Roles\Domain\ValueObjects\RoleName;
use Spatie\Permission\Models\Role;

final readonly class DeleteRole
{
    public function __construct(private RoleRepository $roles)
    {
        //
    }

    public function handle(Role $role): void
    {
        DB::transaction(function () use ($role): void {
            $role = $this->roles->lockForUpdate($role);
            $name = new RoleName($role->name);

            if ($name->isSystem()) {
                throw SystemRoleProtected::cannotDelete($name);
            }

            /** @var list<string> $permissions */
            $permissions = $role->permissions()->pluck('name')->sort()->values()->all();

            $this->roles->delete($role);

            AuditLog::record(
                log: 'roles',
                event: 'deleted',
                subject: $role,
                properties: [
                    'attributes' => ['name' => $role->name, 'permissions' => $permissions],
                ],
            );
        });
    }
}
