<?php

declare(strict_types=1);

namespace Modules\Roles\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Roles\Domain\Repositories\RoleRepository;
use Modules\Roles\Domain\ValueObjects\RoleName;
use Spatie\Permission\Models\Role;

final readonly class CreateRole
{
    public function __construct(private RoleRepository $roles)
    {
        //
    }

    /**
     * @param  list<string>  $permissions
     */
    public function handle(string $name, array $permissions): Role
    {
        $name = new RoleName($name);

        return DB::transaction(function () use ($name, $permissions): Role {
            $role = $this->roles->create((string) $name);

            $this->roles->syncPermissions($role, $permissions);

            activity('roles')
                ->performedOn($role)
                ->event('created')
                ->withProperties([
                    'attributes' => ['name' => (string) $name, 'permissions' => $permissions],
                ])
                ->log('created');

            return $role;
        });
    }
}
