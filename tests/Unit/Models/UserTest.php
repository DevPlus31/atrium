<?php

declare(strict_types=1);

use App\Models\User;
use Carbon\CarbonImmutable;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

test('to array', function (): void {
    $user = User::factory()->create()->refresh();

    expect(array_keys($user->toArray()))
        ->toBe([
            'id',
            'name',
            'email',
            'email_verified_at',
            'two_factor_confirmed_at',
            'created_at',
            'updated_at',
            'appearance',
            'theme',
            'layout',
            'locale',
            'timezone',
            'notify_by_email',
        ]);
});

test('only a super-admin manages another super-admin', function (): void {
    $superAdminRole = Role::findOrCreate(User::SUPER_ADMIN_ROLE);
    $admin = User::factory()->create();
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole($superAdminRole);

    $plain = User::factory()->create();

    expect($admin->isSuperAdmin())->toBeFalse()
        ->and($superAdmin->isSuperAdmin())->toBeTrue()
        ->and($admin->canManage($plain))->toBeTrue()
        ->and($admin->canManage($superAdmin))->toBeFalse()
        ->and($superAdmin->canManage($superAdmin))->toBeTrue();
});

test('manages only accounts whose permissions the user holds', function (): void {
    $actor = User::factory()->create();
    $actor->givePermissionTo(Permission::findOrCreate('users.update'));

    $peer = User::factory()->create();
    $peer->givePermissionTo('users.update');

    $stronger = User::factory()->create();
    $stronger->givePermissionTo(Permission::findOrCreate('roles.update'));

    expect($actor->canManage($peer))->toBeTrue()
        ->and($actor->canManage($stronger))->toBeFalse();
});

test('grants only roles and permissions the user holds', function (): void {
    Permission::findOrCreate('audit.view');
    Permission::findOrCreate('users.view');
    $superAdminRole = Role::findOrCreate(User::SUPER_ADMIN_ROLE);
    $auditor = Role::findOrCreate('auditor')->givePermissionTo('audit.view');
    $user = User::factory()->create();
    $user->givePermissionTo('users.view');

    expect($user->canGrantRole($auditor))->toBeFalse()
        ->and($user->canGrantRole($superAdminRole))->toBeFalse()
        ->and($user->canGrantPermission('users.view'))->toBeTrue()
        ->and($user->canGrantPermission('audit.view'))->toBeFalse();

    $user->givePermissionTo('audit.view');

    expect($user->refresh()->canGrantRole($auditor->refresh()))->toBeTrue();
});

test('writes dates for the user in their own timezone and language', function (): void {
    $user = User::factory()->create(['timezone' => 'Asia/Tokyo', 'locale' => 'fr']);
    $moment = CarbonImmutable::parse('2026-01-15 23:30:00', 'UTC');

    $local = $user->toUserTime($moment);

    expect($local->format('Y-m-d H:i'))->toBe('2026-01-16 08:30')
        ->and($local->translatedFormat('F'))->toBe('janvier')
        ->and($moment->getTimezone()->getName())->toBe('UTC');
});

test('falls back to the application timezone and language', function (): void {
    $user = User::factory()->create(['timezone' => null, 'locale' => null]);

    $local = $user->toUserTime(CarbonImmutable::parse('2026-01-15 23:30:00', 'UTC'));

    expect($local->getTimezone()->getName())->toBe(config('app.timezone'))
        ->and($local->locale)->toBe(config('app.locale'))
        ->and($user->preferredLocale())->toBeNull();
});

test('sends notifications in the language the user chose', function (): void {
    expect(User::factory()->create(['locale' => 'fr'])->preferredLocale())->toBe('fr');
});
