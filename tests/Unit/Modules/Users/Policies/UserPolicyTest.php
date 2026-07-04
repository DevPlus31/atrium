<?php

declare(strict_types=1);

use App\Models\User;
use Modules\Users\Policies\UserPolicy;
use Spatie\Permission\Models\Permission;

beforeEach(function (): void {
    foreach (['users.view', 'users.create', 'users.update', 'users.delete', 'users.export'] as $permission) {
        Permission::findOrCreate($permission);
    }

    $this->policy = new UserPolicy();
});

it('allows viewing any users only with the users.view permission', function (): void {
    $user = User::factory()->create();

    expect($this->policy->viewAny($user))->toBeFalse();

    $user->givePermissionTo('users.view');

    expect($this->policy->viewAny($user->refresh()))->toBeTrue();
});

it('allows creating users only with the users.create permission', function (): void {
    $user = User::factory()->create();

    expect($this->policy->create($user))->toBeFalse();

    $user->givePermissionTo('users.create');

    expect($this->policy->create($user->refresh()))->toBeTrue();
});

it('allows updating users only with the users.update permission', function (): void {
    $user = User::factory()->create();
    $other = User::factory()->create();

    expect($this->policy->update($user, $other))->toBeFalse();

    $user->givePermissionTo('users.update');

    expect($this->policy->update($user->refresh(), $other))->toBeTrue();
});

it('allows deleting other users only with the users.delete permission', function (): void {
    $user = User::factory()->create();
    $other = User::factory()->create();

    expect($this->policy->delete($user, $other))->toBeFalse();

    $user->givePermissionTo('users.delete');

    expect($this->policy->delete($user->refresh(), $other))->toBeTrue();
});

it('denies self-deletion even with the users.delete permission', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo('users.delete');

    expect($this->policy->delete($user->refresh(), $user))->toBeFalse();
});

it('allows exporting users only with the users.export permission', function (): void {
    $user = User::factory()->create();

    expect($this->policy->export($user))->toBeFalse();

    $user->givePermissionTo('users.export');

    expect($this->policy->export($user->refresh()))->toBeTrue();
});
