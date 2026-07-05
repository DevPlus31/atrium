<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\NavRegistry;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    $this->withoutVite();

    $this->artisan('admin:sync-permissions')->assertSuccessful();
});

function systemNavAdmin(): User
{
    $user = User::factory()->create();
    $user->assignRole('admin');

    return $user;
}

it('registers the system nav items for permitted admins', function (): void {
    $admin = systemNavAdmin();

    $navItems = collect($this->app->make(NavRegistry::class)->itemsFor($admin));
    $systemItems = $navItems
        ->where('group', 'System')
        ->whereIn('label', ['Pulse', 'Horizon', 'Logs'])
        ->values();

    expect($systemItems)->toHaveCount(3)
        ->and($systemItems[0]->label)->toBe('Pulse')
        ->and($systemItems[0]->href)->toBe(route('pulse'))
        ->and($systemItems[0]->icon)->toBe('activity')
        ->and($systemItems[0]->sort)->toBe(90)
        ->and($systemItems[0]->external)->toBeTrue()
        ->and($systemItems[1]->label)->toBe('Horizon')
        ->and($systemItems[1]->href)->toBe(route('horizon.index'))
        ->and($systemItems[1]->icon)->toBe('gauge')
        ->and($systemItems[1]->sort)->toBe(91)
        ->and($systemItems[1]->external)->toBeTrue()
        ->and($systemItems[2]->label)->toBe('Logs')
        ->and($systemItems[2]->href)->toBe(route('log-viewer.index'))
        ->and($systemItems[2]->icon)->toBe('file-text')
        ->and($systemItems[2]->sort)->toBe(92)
        ->and($systemItems[2]->external)->toBeTrue();
});

it('hides a system nav item when its permission is missing', function (): void {
    Role::findByName('admin')->revokePermissionTo('system.horizon.view');

    $admin = systemNavAdmin();

    $navItems = collect($this->app->make(NavRegistry::class)->itemsFor($admin));
    $systemItems = $navItems
        ->where('group', 'System')
        ->whereIn('label', ['Pulse', 'Horizon', 'Logs'])
        ->values();

    expect($systemItems->pluck('label')->all())->toBe(['Pulse', 'Logs']);
});

it('hides all system nav items from users without permissions', function (): void {
    $user = User::factory()->create();

    $navItems = collect($this->app->make(NavRegistry::class)->itemsFor($user));

    expect($navItems->where('group', 'System'))->toHaveCount(0);
});

it('shares the system nav items with the external flag over inertia', function (): void {
    $response = $this->actingAs(systemNavAdmin())->get(route('admin.users.index'));

    $response->assertOk()->assertInertia(fn ($page) => $page
        ->where('nav', fn ($nav) => collect($nav)
            ->where('group', 'System')
            ->whereIn('label', ['Pulse', 'Horizon', 'Logs'])
            ->every(fn (array $item): bool => $item['external'] === true)));
});
