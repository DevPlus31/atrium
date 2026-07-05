<?php

declare(strict_types=1);

use Modules\Roles\Actions\DeleteRole;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

it('deletes the role', function (): void {
    $role = Role::findOrCreate('editor');

    new DeleteRole()->handle($role);

    expect(Role::query()->whereKey($role->id)->exists())->toBeFalse();
});

it('writes a deleted activity with the role attributes', function (): void {
    Permission::findOrCreate('users.view');
    $role = Role::findOrCreate('editor');
    $role->givePermissionTo('users.view');

    new DeleteRole()->handle($role);

    $activity = Activity::query()->where('event', 'deleted')->sole();

    expect($activity->log_name)->toBe('roles')
        ->and($activity->getProperty('attributes'))->toBe([
            'name' => 'editor',
            'permissions' => ['users.view'],
        ]);
});

it('refuses to delete system roles', function (string $name): void {
    $role = Role::findOrCreate($name);

    expect(fn () => new DeleteRole()->handle($role))
        ->toThrow(InvalidArgumentException::class, sprintf('The [%s] role is a system role and cannot be deleted.', $name));

    expect(Role::query()->whereKey($role->id)->exists())->toBeTrue()
        ->and(Activity::query()->count())->toBe(0);
})->with(['admin', 'super-admin']);

it('rolls back the transaction when logging fails', function (): void {
    $role = Role::findOrCreate('editor');

    Role::deleted(function (): void {
        throw new RuntimeException('Activity log unavailable.');
    });

    expect(fn () => new DeleteRole()->handle($role))
        ->toThrow(RuntimeException::class);

    expect(Role::query()->whereKey($role->id)->exists())->toBeTrue()
        ->and(Activity::query()->count())->toBe(0);
});
