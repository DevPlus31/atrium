<?php

declare(strict_types=1);

use App\Modules\PermissionRegistry;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    // Isolate from permissions declared by the application's real modules.
    $this->app->instance(PermissionRegistry::class, new PermissionRegistry());
});

it('creates declared permissions', function (): void {
    $registry = $this->app->make(PermissionRegistry::class);
    $registry->declare('users.view', roles: ['admin']);
    $registry->declare('users.delete');

    $this->artisan('admin:sync-permissions')->assertSuccessful();

    expect(Permission::query()->pluck('name')->all())->toEqualCanonicalizing(['users.view', 'users.delete']);
});

it('assigns declared permissions to their default roles', function (): void {
    $registry = $this->app->make(PermissionRegistry::class);
    $registry->declare('users.view', roles: ['admin']);

    $this->artisan('admin:sync-permissions')->assertSuccessful();

    /** @var Role $role */
    $role = Role::findByName('admin');

    expect($role->hasPermissionTo('users.view'))->toBeTrue();
});

it('is idempotent', function (): void {
    $registry = $this->app->make(PermissionRegistry::class);
    $registry->declare('users.view', roles: ['admin']);

    $this->artisan('admin:sync-permissions')->assertSuccessful();
    $this->artisan('admin:sync-permissions')->assertSuccessful();

    /** @var Role $role */
    $role = Role::findByName('admin');

    expect(Permission::query()->count())->toBe(1)
        ->and(Role::query()->count())->toBe(1)
        ->and($role->permissions()->count())->toBe(1);
});

it('prunes permissions that are no longer declared', function (): void {
    Permission::findOrCreate('stale.permission');

    $this->app->make(PermissionRegistry::class)->declare('users.view');

    $this->artisan('admin:sync-permissions')->assertSuccessful();

    expect(Permission::query()->pluck('name')->all())->toBe(['users.view']);
});

it('prunes every permission when none are declared', function (): void {
    Permission::findOrCreate('stale.permission');

    $this->artisan('admin:sync-permissions')->assertSuccessful();

    expect(Permission::query()->count())->toBe(0);
});
