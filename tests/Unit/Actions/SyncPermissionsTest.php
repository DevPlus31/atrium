<?php

declare(strict_types=1);

use App\Actions\SyncPermissions;
use App\Modules\PermissionRegistry;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

it('syncs declared permissions and their default roles', function (): void {
    $registry = new PermissionRegistry();
    $registry->declare('widgets.view', roles: ['admin']);
    Permission::findOrCreate('stale.permission');

    expect(resolve(SyncPermissions::class)->handle($registry))->toBe(1)
        ->and(Permission::query()->pluck('name')->all())->toBe(['widgets.view'])
        ->and(Role::findByName('admin')->hasPermissionTo('widgets.view'))->toBeTrue();
});

it('rolls back every change when a step fails', function (): void {
    $registry = new PermissionRegistry();
    $registry->declare('widgets.view', roles: ['admin']);
    Permission::findOrCreate('stale.permission');

    Role::creating(function (): void {
        throw new RuntimeException('Role table unavailable.');
    });

    expect(fn () => resolve(SyncPermissions::class)->handle($registry))->toThrow(RuntimeException::class)
        ->and(Permission::query()->pluck('name')->all())->toBe(['stale.permission']);
});
