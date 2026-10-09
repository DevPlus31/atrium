<?php

declare(strict_types=1);

use Modules\Roles\Domain\Repositories\RoleRepository;
use Modules\Roles\Infrastructure\Repositories\EloquentRoleRepository;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

it('is the bound role repository', function (): void {
    expect(resolve(RoleRepository::class))->toBeInstanceOf(EloquentRoleRepository::class);
});

it('creates, renames, assigns and deletes roles', function (): void {
    Permission::findOrCreate('users.view');
    $roles = resolve(RoleRepository::class);

    $role = $roles->create('editor');
    $roles->rename($role, 'author');
    $roles->syncPermissions($role, ['users.view']);

    expect($role->refresh()->name)->toBe('author')
        ->and($role->permissions->pluck('name')->all())->toBe(['users.view']);

    $roles->delete($role);

    expect(Role::query()->exists())->toBeFalse();
});

it('re-reads a role under a row lock', function (): void {
    $role = Role::findOrCreate('editor');

    expect(resolve(RoleRepository::class)->lockForUpdate($role)->is($role))->toBeTrue();
});
