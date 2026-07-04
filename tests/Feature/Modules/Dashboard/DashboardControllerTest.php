<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\NavRegistry;
use Illuminate\Support\Collection;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    $this->withoutVite();

    $this->artisan('admin:sync-permissions')->assertSuccessful();
});

function dashboardAdmin(): User
{
    $user = User::factory()->create();
    $user->assignRole('admin');

    return $user;
}

it('redirects guests to the login page', function (): void {
    $response = $this->get('/admin/dashboard');

    $response->assertRedirectToRoute('login');
});

it('forbids authenticated users without the admin role', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/admin/dashboard');

    $response->assertForbidden();
});

it('forbids admins without the dashboard.view permission', function (): void {
    Role::findByName('admin')->revokePermissionTo('dashboard.view');

    $response = $this->actingAs(dashboardAdmin())->get(route('admin.dashboard.index'));

    $response->assertForbidden();
});

it('registers the dashboard nav item for permitted admins', function (): void {
    $admin = dashboardAdmin();

    $navItems = $this->app->make(NavRegistry::class)->itemsFor($admin);
    $navItem = collect($navItems)->firstWhere('label', 'Dashboard');

    expect($navItem)->not->toBeNull()
        ->and($navItem?->href)->toBe(route('admin.dashboard.index'))
        ->and($navItem?->icon)->toBe('layout-dashboard')
        ->and($navItem?->group)->toBeNull()
        ->and($navItem?->sort)->toBe(0);
});

it('hides the dashboard nav item without the dashboard.view permission', function (): void {
    $user = User::factory()->create();

    $navItems = $this->app->make(NavRegistry::class)->itemsFor($user);

    expect(collect($navItems)->firstWhere('label', 'Dashboard'))->toBeNull();
});

it('renders the dashboard with ordered widget descriptors and deferred data props', function (): void {
    $admin = dashboardAdmin();

    $response = $this->actingAs($admin)->get(route('admin.dashboard.index'));

    $response->assertOk()->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
        ->component('dashboard::index', false)
        ->has('widgets', 2)
        ->where('widgets.0.key', 'users.total')
        ->where('widgets.0.sort', 0)
        ->where('widgets.1.key', 'users.recent')
        ->where('widgets.1.sort', 10)
        ->missing('widget:users.total')
        ->missing('widget:users.recent'));
});

it('resolves the deferred widget props to their data objects', function (): void {
    config()->set('inertia.testing.ensure_pages_exist', false);

    $admin = dashboardAdmin();
    User::factory()->create([
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'created_at' => now()->subDays(2),
    ]);

    $response = $this->actingAs($admin)->get(route('admin.dashboard.index'));

    $response->assertOk()->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
        ->component('dashboard::index', false)
        ->loadDeferredProps('widgets', fn (AssertableInertia $reloaded): AssertableInertia => $reloaded
            ->where('widget:users.total', function (Collection $data): bool {
                $series = collect($data->get('series'));

                expect($data->get('total'))->toBe(2)
                    ->and($series)->toHaveCount(14)
                    ->and($series->last())->toBe(['date' => now()->toDateString(), 'count' => 1])
                    ->and($series->get(11))->toBe(['date' => now()->subDays(2)->toDateString(), 'count' => 1])
                    ->and($series->sum('count'))->toBe(2);

                return true;
            })
            ->where('widget:users.recent', function (Collection $data) use ($admin): bool {
                $users = collect($data->get('users'));

                expect($users)->toHaveCount(2)
                    ->and($users->first()['id'] ?? null)->toBe($admin->id)
                    ->and($users->last())->toMatchArray([
                        'name' => 'Jane Doe',
                        'email' => 'jane@example.com',
                        'created_at' => now()->subDays(2)->toIso8601String(),
                    ]);

                return true;
            })));
});

it('omits the users widgets without the users.view permission', function (): void {
    Role::findByName('admin')->revokePermissionTo('users.view');

    $response = $this->actingAs(dashboardAdmin())->get(route('admin.dashboard.index'));

    $response->assertOk()->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
        ->component('dashboard::index', false)
        ->has('widgets', 0)
        ->missing('widget:users.total')
        ->missing('widget:users.recent'));
});
