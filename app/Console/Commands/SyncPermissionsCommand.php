<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Modules\PermissionRegistry;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

#[Description('Sync module-declared permissions and default role assignments to the database')]
#[Signature('admin:sync-permissions')]
final class SyncPermissionsCommand extends Command
{
    public function handle(PermissionRegistry $registry, PermissionRegistrar $registrar): int
    {
        $declared = $registry->permissions();

        foreach ($declared as $permission) {
            Permission::findOrCreate($permission);
        }

        Permission::query()->whereNotIn('name', $declared)->delete();

        foreach ($registry->roleAssignments() as $permission => $roles) {
            foreach ($roles as $roleName) {
                /** @var Role $role */
                $role = Role::findOrCreate($roleName);

                $role->givePermissionTo($permission);
            }
        }

        $registrar->forgetCachedPermissions();

        $this->components->info(sprintf('Synced %d permissions.', count($declared)));

        return self::SUCCESS;
    }
}
