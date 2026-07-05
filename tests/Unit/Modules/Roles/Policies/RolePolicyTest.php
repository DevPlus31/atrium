<?php

declare(strict_types=1);

use App\Models\User;
use Modules\Roles\Policies\RolePolicy;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    foreach (['roles.view', 'roles.create', 'roles.update', 'roles.delete'] as $permission) {
        Permission::findOrCreate($permission);
    }

    $this->policy = new RolePolicy();
});

it('allows viewing any roles only with the roles.view permission', function (): void {
    $user = User::factory()->create();

    expect($this->policy->viewAny($user))->toBeFalse();

    $user->givePermissionTo('roles.view');

    expect($this->policy->viewAny($user->refresh()))->toBeTrue();
});

it('allows creating roles only with the roles.create permission', function (): void {
    $user = User::factory()->create();

    expect($this->policy->create($user))->toBeFalse();

    $user->givePermissionTo('roles.create');

    expect($this->policy->create($user->refresh()))->toBeTrue();
});

it('allows updating roles only with the roles.update permission', function (): void {
    $user = User::factory()->create();

    expect($this->policy->update($user))->toBeFalse();

    $user->givePermissionTo('roles.update');

    expect($this->policy->update($user->refresh()))->toBeTrue();
});

it('allows deleting roles only with the roles.delete permission', function (): void {
    $user = User::factory()->create();
    $role = Role::findOrCreate('editor');

    expect($this->policy->delete($user, $role))->toBeFalse();

    $user->givePermissionTo('roles.delete');

    expect($this->policy->delete($user->refresh(), $role))->toBeTrue();
});

it('denies deleting system roles even with the roles.delete permission', function (string $name): void {
    $user = User::factory()->create();
    $user->givePermissionTo('roles.delete');

    $role = Role::findOrCreate($name);

    expect($this->policy->delete($user->refresh(), $role))->toBeFalse();
})->with(['admin', 'super-admin']);
