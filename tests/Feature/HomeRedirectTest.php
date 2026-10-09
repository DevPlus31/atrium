<?php

declare(strict_types=1);

use App\Enums\Area;
use App\Models\User;
use App\Modules\NavRegistry;
use Illuminate\Support\Facades\Route;
use Laravel\Pennant\Feature;

it('redirects the root to the dashboard', function (): void {
    $this->get('/')->assertRedirect(route('dashboard'));
});

it('redirects the dashboard into the admin panel for panel users', function (): void {
    $this->artisan('admin:sync-permissions')->assertSuccessful();

    $this->actingAs(adminUser())->get(route('dashboard'))->assertRedirect(route('admin.dashboard.index'));
});

it('sends users without panel access to the member home', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('member.dashboard'));
});

it('sends users without panel access to their profile when no module offers a member page', function (): void {
    $user = User::factory()->create();
    Feature::for($user)->deactivate('module:dashboard');

    $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('user-profile.edit'));
});

it('sends guests from the dashboard to the login page', function (): void {
    $this->get(route('dashboard'))->assertRedirectToRoute('login');
});

it('sends users without panel access to whichever member page a module offers first', function (): void {
    Route::get('my-orders', fn (): string => 'orders')->name('member.orders');
    Route::getRoutes()->refreshNameLookups();
    Feature::define('module:example', fn (): bool => true);
    $this->app->make(NavRegistry::class)->add(module: 'example', label: 'My orders', routeName: 'member.orders', area: Area::Member);

    $user = User::factory()->create();
    Feature::for($user)->deactivate('module:dashboard');

    $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('member.orders'));
});

it('lands panel users on the next admin page when the dashboard module is switched off', function (): void {
    $this->artisan('admin:sync-permissions')->assertSuccessful();
    config()->set('modules.disabled', ['dashboard']);

    $admin = adminUser();
    $first = $this->app->make(NavRegistry::class)->itemsFor($admin)[0]->href;

    expect($first)->not->toBe(route('admin.dashboard.index'));

    $this->actingAs($admin)->get(route('dashboard'))->assertRedirect($first);

    $this->get(route('admin.dashboard.index'))->assertNotFound();
});
