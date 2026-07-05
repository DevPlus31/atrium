<?php

declare(strict_types=1);

namespace Modules\Roles\Actions;

use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

final readonly class UpdateRole
{
    /**
     * @param  list<string>  $permissions
     */
    public function handle(Role $role, string $name, array $permissions): Role
    {
        return DB::transaction(function () use ($role, $name, $permissions): Role {
            $old = [
                'name' => $role->name,
                'permissions' => $role->permissions()->pluck('name')->sort()->values()->all(),
            ];

            $role->update(['name' => $name]);

            $role->syncPermissions($permissions);

            activity('roles')
                ->performedOn($role)
                ->event('updated')
                ->withProperties([
                    'old' => $old,
                    'attributes' => ['name' => $name, 'permissions' => $permissions],
                ])
                ->log('updated');

            return $role->refresh();
        });
    }
}
