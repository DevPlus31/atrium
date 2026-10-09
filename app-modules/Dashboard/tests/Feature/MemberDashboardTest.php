<?php

declare(strict_types=1);

use App\Enums\Area;
use App\Models\User;
use App\Modules\NavRegistry;
use Inertia\Testing\AssertableInertia;
use Laravel\Pennant\Feature;
use Spatie\Permission\Models\Permission;

beforeEach(function (): void {
    $this->withoutVite();

    $this->artisan('admin:sync-permissions')->assertSuccessful();
});

it('redirects guests to the login page', function (): void {
    $this->get(route('member.dashboard'))->assertRedirectToRoute('login');
});

it('asks unverified members to verify their email first', function (): void {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)->get(route('member.dashboard'))->assertRedirectToRoute('verification.notice');
});

it('sends panel users to the admin dashboard', function (): void {
    $this->actingAs(adminUser())->get(route('member.dashboard'))->assertRedirectToRoute('admin.dashboard.index');
});

it('is not found when the dashboard module is off for the member', function (): void {
    $user = User::factory()->create();
    Feature::for($user)->deactivate('module:dashboard');

    $this->actingAs($user)->get(route('member.dashboard'))->assertNotFound();
});

it('renders its member widgets first and defers their data', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('member.dashboard'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('dashboard::home')
            ->where('widgets.0', ['key' => 'dashboard.access', 'prop' => 'widget:dashboard_access', 'sort' => 0])
            ->where('widgets.1', ['key' => 'dashboard.account', 'prop' => 'widget:dashboard_account', 'sort' => 10])
            ->missing('widget:dashboard_account'));
});

it('resolves its member widgets for the viewer', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate('reports.view'));

    $this->actingAs($user)->get(route('member.dashboard'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('dashboard::home')
            ->loadDeferredProps('widgets', fn (AssertableInertia $reloaded): AssertableInertia => $reloaded
                ->where('widget:dashboard_access', null)
                ->where('widget:dashboard_account.two_factor_enabled', true)
                ->etc()));
});

it('offers members a home nav item that panel users do not see', function (): void {
    $nav = $this->app->make(NavRegistry::class);
    $member = User::factory()->create();

    expect(collect($nav->itemsFor($member, Area::Member))->firstWhere('label', 'Home')?->href)
        ->toBe(route('member.dashboard'))
        ->and(collect($nav->itemsFor(adminUser(), Area::Admin))->firstWhere('label', 'Home'))->toBeNull();
});
