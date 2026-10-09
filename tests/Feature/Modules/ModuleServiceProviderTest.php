<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Middleware\EnsureModuleIsEnabled;
use App\Modules\NavRegistry;
use App\Modules\PermissionRegistry;
use App\Modules\WidgetRegistry;
use Illuminate\Support\Facades\Route;
use Laravel\Pennant\Feature;
use Spatie\Permission\Models\Role;
use Tests\Fixtures\Modules\BareModule\Providers\BareModuleServiceProvider;
use Tests\Fixtures\Modules\TestModule\Providers\TestModuleServiceProvider;

it('defines the module feature as active by default', function (): void {
    $this->app->register(BareModuleServiceProvider::class);

    $user = User::factory()->create();

    expect(Feature::for($user)->active('module:bare-module'))->toBeTrue();
});

it('registers module routes inside the admin group', function (): void {
    $this->app->register(BareModuleServiceProvider::class);

    expect(Route::has('admin.bare-module.index'))->toBeTrue();

    $route = Route::getRoutes()->getByName('admin.bare-module.index');

    expect($route)->not->toBeNull()
        ->and($route->uri())->toBe('admin/bare-module')
        ->and($route->gatherMiddleware())->toContain(
            'web',
            'auth',
            'verified',
            'can:access-panel',
            EnsureModuleIsEnabled::class.':bare-module',
        );
});

it('registers the module migrations path', function (): void {
    $this->app->register(BareModuleServiceProvider::class);

    $paths = array_map(
        static fn (string $path): string => str_replace('\\', '/', $path),
        $this->app->make('migrator')->paths(),
    );

    expect($paths)->toContain(
        str_replace('\\', '/', dirname((string) new ReflectionClass(BareModuleServiceProvider::class)->getFileName(), 2).'/Database/Migrations'),
    );
});

it('invokes the module registry hooks on boot', function (): void {
    $this->app->register(TestModuleServiceProvider::class);

    $permissions = $this->app->make(PermissionRegistry::class);

    expect($permissions->permissions())->toContain('test-module.view')
        ->and($permissions->roleAssignments()['test-module.view'])->toBe(['admin']);

    Role::findOrCreate('super-admin');

    $user = User::factory()->create();
    $user->assignRole('super-admin');

    $navItems = $this->app->make(NavRegistry::class)->itemsFor($user);
    $navItem = collect($navItems)->firstWhere('label', 'Test Module');

    expect($navItem)->not->toBeNull()
        ->and($navItem?->href)->toBe(route('admin.test-module.index'));

    $widget = $this->app->make(WidgetRegistry::class)->resolveFor($user, 'test-module.stats');

    expect($widget)->not->toBeNull()
        ->and($widget?->toArray())->toBe(['count' => 3]);
});

it('teaches Inertia where each module keeps its pages', function (): void {
    $finder = $this->app->make('inertia.view-finder');

    expect($finder->find('dashboard::home'))->toBe(base_path('app-modules/Dashboard/resources/js/pages/home.tsx'))
        ->and(fn () => $finder->find('dashboard::missing'))->toThrow(InvalidArgumentException::class);
});

it('addresses module pages by the lowercased module folder, like the frontend resolver', function (): void {
    $this->app->register(TestModuleServiceProvider::class);

    $hints = $this->app->make('inertia.view-finder')->getHints();

    expect($hints['testmodule'] ?? [])->toBe([base_path('tests/Fixtures/Modules/TestModule/resources/js/pages')])
        ->and($hints)->not->toHaveKey('test-module');
});

it("lets a module add a page to every user's account settings", function (): void {
    $this->app->register(TestModuleServiceProvider::class);

    $this->actingAs(User::factory()->create())
        ->get(route('user-profile.edit'))
        ->assertInertia(fn ($page) => $page
            ->where('settingsNav.0.label', 'Test Module settings')
            ->where('settingsNav.0.href', route('admin.test-module.index')));
});

it('loads the translations a module ships in its own lang folder', function (): void {
    $this->app->register(TestModuleServiceProvider::class);

    expect(__('Test Module', locale: 'xx'))->toBe('Module de test');
});
