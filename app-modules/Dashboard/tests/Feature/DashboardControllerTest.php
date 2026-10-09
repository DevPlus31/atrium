<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\NavRegistry;
use Illuminate\Support\Collection;
use Inertia\Testing\AssertableInertia;

beforeEach(function (): void {
    $this->artisan('admin:sync-permissions')->assertSuccessful();
});

it('keeps guests and non-admins out', function (): void {
    assertAdminOnly('get', route('admin.dashboard.index'));
});

it('forbids admins without the dashboard.view permission', function (): void {
    $response = $this->actingAs(adminWithout('dashboard.view'))->get(route('admin.dashboard.index'));

    $response->assertForbidden();
});

it('registers the dashboard nav item for permitted admins', function (): void {
    $admin = adminUser();

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
    $admin = adminUser();

    $response = $this->actingAs($admin)->get(route('admin.dashboard.index'));

    $response->assertOk()->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
        ->component('dashboard::index')
        ->has('widgets', 2)
        ->where('widgets.0.key', 'users.total')
        ->where('widgets.0.prop', 'widget:users_total')
        ->where('widgets.0.sort', 0)
        ->where('widgets.1.key', 'users.recent')
        ->where('widgets.1.prop', 'widget:users_recent')
        ->where('widgets.1.sort', 10)
        ->missing('widget:users_total')
        ->missing('widget:users_recent'));
});

it('sends each deferred widget under its flat descriptor prop name', function (): void {
    $admin = adminUser();

    $page = $this->actingAs($admin)->get(route('admin.dashboard.index'))->viewData('page');
    $propNames = array_column($page['props']['widgets'], 'prop');

    $response = $this->actingAs($admin)->withHeaders([
        'X-Inertia' => 'true',
        'X-Inertia-Version' => (string) $page['version'],
        'X-Inertia-Partial-Component' => $page['component'],
        'X-Inertia-Partial-Data' => implode(',', $propNames),
    ])->get(route('admin.dashboard.index'));

    $response->assertOk();

    expect($propNames)->toBe(['widget:users_total', 'widget:users_recent'])
        ->and(array_keys($response->json('props')))->toContain(...$propNames);
});

it('resolves the deferred widget props to their data objects', function (): void {
    $admin = adminUser();
    User::factory()->create([
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'created_at' => now()->subDays(2),
    ]);

    $response = $this->actingAs($admin)->get(route('admin.dashboard.index'));

    $response->assertOk()->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
        ->component('dashboard::index')
        ->loadDeferredProps('widgets', fn (AssertableInertia $reloaded): AssertableInertia => $reloaded
            ->where('widget:users_total', function (Collection $data): bool {
                $series = collect($data->get('series'));

                expect($data->get('total'))->toBe(2)
                    ->and($series)->toHaveCount(14)
                    ->and($series->last())->toBe(['date' => now()->toDateString(), 'count' => 1])
                    ->and($series->get(11))->toBe(['date' => now()->subDays(2)->toDateString(), 'count' => 1])
                    ->and($series->sum('count'))->toBe(2);

                return true;
            })
            ->where('widget:users_recent', function (Collection $data) use ($admin): bool {
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
    $response = $this->actingAs(adminWithout('users.view'))->get(route('admin.dashboard.index'));

    $response->assertOk()->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
        ->component('dashboard::index')
        ->has('widgets', 0)
        ->missing('widget:users_total')
        ->missing('widget:users_recent'));
});
