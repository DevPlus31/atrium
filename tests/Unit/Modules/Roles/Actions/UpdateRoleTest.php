<?php

declare(strict_types=1);

use Modules\Roles\Actions\UpdateRole;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

it('updates the role and syncs permissions', function (): void {
    Permission::findOrCreate('users.view');
    $role = Role::findOrCreate('editor');

    $updated = new UpdateRole()->handle(
        role: $role,
        name: 'publisher',
        permissions: ['users.view'],
    );

    expect($updated->name)->toBe('publisher')
        ->and($updated->hasPermissionTo('users.view'))->toBeTrue();
});

it('writes an updated activity with the old and new attributes', function (): void {
    Permission::findOrCreate('users.view');
    $role = Role::findOrCreate('editor');
    $role->givePermissionTo('users.view');

    new UpdateRole()->handle(
        role: $role,
        name: 'publisher',
        permissions: [],
    );

    $activity = Activity::query()->where('event', 'updated')->sole();

    expect($activity->log_name)->toBe('roles')
        ->and($activity->subject_id)->toEqual($role->id)
        ->and($activity->getProperty('old'))->toBe([
            'name' => 'editor',
            'permissions' => ['users.view'],
        ])
        ->and($activity->getProperty('attributes'))->toBe([
            'name' => 'publisher',
            'permissions' => [],
        ]);
});

it('rolls back the transaction when syncing permissions fails', function (): void {
    $role = Role::findOrCreate('editor');

    expect(fn (): Role => new UpdateRole()->handle(
        role: $role,
        name: 'publisher',
        permissions: ['missing.permission'],
    ))->toThrow(PermissionDoesNotExist::class);

    expect($role->refresh()->name)->toBe('editor')
        ->and(Activity::query()->count())->toBe(0);
});
