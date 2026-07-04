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
            'role:admin',
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

    expect($navItems)->toHaveCount(1)
        ->and($navItems[0]->label)->toBe('Test Module')
        ->and($navItems[0]->href)->toBe(route('admin.test-module.index'));

    $widgets = $this->app->make(WidgetRegistry::class)->widgetsFor($user);

    expect($widgets)->toHaveCount(1)
        ->and($widgets[0]['key'])->toBe('test-module.stats')
        ->and($widgets[0]['data']->toArray())->toBe(['count' => 3]);
});
