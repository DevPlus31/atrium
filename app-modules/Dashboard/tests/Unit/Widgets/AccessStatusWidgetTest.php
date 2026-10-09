<?php

declare(strict_types=1);

use App\Models\User;
use Modules\Dashboard\Widgets\AccessStatusWidget;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

it('tells an account without any role that it awaits access', function (): void {
    $user = User::factory()->create(['email' => 'jane@example.com']);

    expect(new AccessStatusWidget()($user)?->toArray())->toBe(['email' => 'jane@example.com']);
});

it('tells an account whose roles grant nothing that it awaits access', function (): void {
    $user = User::factory()->create();
    $user->assignRole(Role::findOrCreate('customer'));

    expect(new AccessStatusWidget()($user))->not->toBeNull();
});

it('has nothing to show once the account holds a permission', function (): void {
    $user = User::factory()->create();
    $user->assignRole(Role::findOrCreate('customer')->givePermissionTo(Permission::findOrCreate('reports.view')));

    expect(new AccessStatusWidget()($user))->toBeNull();
});
