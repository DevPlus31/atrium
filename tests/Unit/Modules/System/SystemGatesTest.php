<?php

declare(strict_types=1);

use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    $this->artisan('admin:sync-permissions')->assertSuccessful();
});

function systemGatesAdmin(): User
{
    $user = User::factory()->create();
    $user->assignRole('admin');

    return $user;
}

it('allows viewPulse only with the system.pulse.view permission', function (): void {
    $admin = systemGatesAdmin();
    $user = User::factory()->create();

    expect($admin->can('viewPulse'))->toBeTrue()
        ->and($user->can('viewPulse'))->toBeFalse();
});

it('allows viewHorizon only with the system.horizon.view permission', function (): void {
    $admin = systemGatesAdmin();
    $user = User::factory()->create();

    expect($admin->can('viewHorizon'))->toBeTrue()
        ->and($user->can('viewHorizon'))->toBeFalse();
});

it('allows viewLogViewer only with the system.logs.view permission', function (): void {
    $admin = systemGatesAdmin();
    $user = User::factory()->create();

    expect($admin->can('viewLogViewer'))->toBeTrue()
        ->and($user->can('viewLogViewer'))->toBeFalse();
});

it('allows log downloads only with the system.logs.view permission', function (): void {
    $admin = systemGatesAdmin();
    $user = User::factory()->create();

    expect($admin->can('downloadLogFile'))->toBeTrue()
        ->and($admin->can('downloadLogFolder'))->toBeTrue()
        ->and($user->can('downloadLogFile'))->toBeFalse()
        ->and($user->can('downloadLogFolder'))->toBeFalse();
});

it('denies log deletion even for permitted admins', function (): void {
    $admin = systemGatesAdmin();

    expect($admin->can('deleteLogFile'))->toBeFalse()
        ->and($admin->can('deleteLogFolder'))->toBeFalse();
});

it('lets super admins pass the system gates', function (): void {
    Role::findOrCreate('super-admin');

    $user = User::factory()->create();
    $user->assignRole('super-admin');

    expect($user->can('viewPulse'))->toBeTrue()
        ->and($user->can('viewHorizon'))->toBeTrue()
        ->and($user->can('viewLogViewer'))->toBeTrue();
});
