<?php

declare(strict_types=1);

namespace App\Actions;

use App\Modules\PermissionRegistry;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final readonly class SyncPermissions
{
    public function __construct(private PermissionRegistrar $registrar)
    {
        //
    }

    /**
     * Create the declared permissions, prune undeclared ones and grant the
     * default role assignments in one transaction. Returns the number of
     * declared permissions.
     */
    public function handle(PermissionRegistry $registry): int
    {
        $declared = $registry->permissions();

        DB::transaction(function () use ($registry, $declared): void {
            foreach ($declared as $permission) {
                Permission::findOrCreate($permission);
            }

            Permission::query()->whereNotIn('name', $declared)->delete();

            foreach ($registry->roleAssignments() as $permission => $roles) {
                foreach ($roles as $roleName) {
                    Role::findOrCreate($roleName)->givePermissionTo($permission);
                }
            }
        });

        $this->registrar->forgetCachedPermissions();

        return count($declared);
    }
}
