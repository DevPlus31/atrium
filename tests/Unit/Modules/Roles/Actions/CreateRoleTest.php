<?php

declare(strict_types=1);

use Modules\Roles\Actions\CreateRole;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

it('creates a role with synced permissions', function (): void {
    Permission::findOrCreate('users.view');

    $role = new CreateRole()->handle(name: 'editor', permissions: ['users.view']);

    expect($role->name)->toBe('editor')
        ->and($role->hasPermissionTo('users.view'))->toBeTrue();
});

it('writes a created activity with the given attributes', function (): void {
    Permission::findOrCreate('users.view');

    $role = new CreateRole()->handle(name: 'editor', permissions: ['users.view']);

    $activity = Activity::query()->where('event', 'created')->sole();

    expect($activity->log_name)->toBe('roles')
        ->and($activity->subject_id)->toEqual($role->id)
        ->and($activity->getProperty('attributes'))->toBe([
            'name' => 'editor',
            'permissions' => ['users.view'],
        ]);
});

it('rolls back the transaction when syncing permissions fails', function (): void {
    expect(fn (): Role => new CreateRole()->handle(name: 'editor', permissions: ['missing.permission']))
        ->toThrow(PermissionDoesNotExist::class);

    expect(Role::query()->where('name', 'editor')->exists())->toBeFalse()
        ->and(Activity::query()->count())->toBe(0);
});
