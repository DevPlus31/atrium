<?php

declare(strict_types=1);

namespace Modules\Roles\Actions;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Roles\Policies\RolePolicy;
use Spatie\Permission\Models\Role;

final readonly class DeleteRole
{
    public function handle(Role $role): void
    {
        if (RolePolicy::isSystemRole($role)) {
            throw new InvalidArgumentException(sprintf('The [%s] role is a system role and cannot be deleted.', $role->name));
        }

        DB::transaction(function () use ($role): void {
            /** @var list<string> $permissions */
            $permissions = $role->permissions()->pluck('name')->sort()->values()->all();

            $role->delete();

            activity('roles')
                ->performedOn($role)
                ->event('deleted')
                ->withProperties([
                    'attributes' => ['name' => $role->name, 'permissions' => $permissions],
                ])
                ->log('deleted');
        });
    }
}
