<?php

declare(strict_types=1);

namespace Modules\Roles\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Roles\Actions\CreateRole;
use Modules\Roles\Actions\DeleteRole;
use Modules\Roles\Actions\UpdateRole;
use Modules\Roles\Data\RoleData;
use Modules\Roles\Http\Requests\StoreRoleRequest;
use Modules\Roles\Http\Requests\UpdateRoleRequest;
use Modules\Roles\Queries\RolesIndexQuery;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

final readonly class RoleController
{
    #[Authorize('viewAny', Role::class)]
    public function index(RolesIndexQuery $query): Response
    {
        return Inertia::render('roles::index', [
            'roles' => RoleData::collect($query->paginate(), PaginatedDataCollection::class),
            'permissions' => $this->permissionNames(),
        ]);
    }

    #[Authorize('create', Role::class)]
    public function create(): Response
    {
        return Inertia::render('roles::create', [
            'permissions' => $this->permissionNames(),
        ]);
    }

    #[Authorize('create', Role::class)]
    public function store(StoreRoleRequest $request, CreateRole $action): RedirectResponse
    {
        $action->handle(
            name: $request->name(),
            permissions: $request->permissions(),
        );

        return to_route('admin.roles.index')->with('success', __('Role created.'));
    }

    #[Authorize('update', 'role')]
    public function edit(Role $role): Response
    {
        return Inertia::render('roles::edit', [
            'role' => RoleData::from($role->load('permissions')),
            'permissions' => $this->permissionNames(),
        ]);
    }

    #[Authorize('update', 'role')]
    public function update(UpdateRoleRequest $request, Role $role, UpdateRole $action): RedirectResponse
    {
        $action->handle(
            role: $role,
            name: $request->name(),
            permissions: $request->permissions(),
        );

        return to_route('admin.roles.index')->with('success', __('Role updated.'));
    }

    #[Authorize('delete', 'role')]
    public function destroy(Role $role, DeleteRole $action): RedirectResponse
    {
        $action->handle($role);

        return to_route('admin.roles.index')->with('success', __('Role deleted.'));
    }

    /**
     * @return list<string>
     */
    private function permissionNames(): array
    {
        /** @var list<string> $names */
        $names = Permission::query()->orderBy('name')->pluck('name')->all();

        return $names;
    }
}
