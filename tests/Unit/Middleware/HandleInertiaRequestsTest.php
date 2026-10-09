<?php

declare(strict_types=1);

use App\Enums\Area;
use App\Http\Middleware\HandleInertiaRequests;
use App\Models\User;
use App\Modules\Data\NavItemData;
use App\Modules\NavRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Pennant\Feature;
use Spatie\Permission\Models\Role;

it('shares app name from config', function (): void {
    $middleware = $this->app->make(HandleInertiaRequests::class);

    $request = Request::create('/', 'GET');

    $shared = $middleware->share($request);

    expect($shared)->toHaveKey('name')
        ->and($shared['name'])->toBe(config('app.name'));
});

it('shares null user when guest', function (): void {
    $middleware = $this->app->make(HandleInertiaRequests::class);

    $request = Request::create('/', 'GET');

    $shared = $middleware->share($request);

    expect($shared)->toHaveKey('auth')
        ->and($shared['auth'])->toHaveKey('user')
        ->and($shared['auth']['user'])->toBeNull();
});

it('shares authenticated user data', function (): void {
    $user = User::factory()->create([
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]);

    $middleware = $this->app->make(HandleInertiaRequests::class);

    $request = Request::create('/', 'GET');
    $request->setUserResolver(fn () => $user);

    $shared = $middleware->share($request);

    expect($shared['auth']['user'])->not->toBeNull()
        ->and($shared['auth']['user']->id)->toBe($user->id)
        ->and($shared['auth']['user']->name)->toBe('Test User')
        ->and($shared['auth']['user']->email)->toBe('test@example.com');
});

it('defaults sidebarOpen to true when no cookie', function (): void {
    $middleware = $this->app->make(HandleInertiaRequests::class);

    $request = Request::create('/', 'GET');

    $shared = $middleware->share($request);

    expect($shared)->toHaveKey('sidebarOpen')
        ->and($shared['sidebarOpen'])->toBeTrue();
});

it('sets sidebarOpen to true when cookie is true', function (): void {
    $middleware = $this->app->make(HandleInertiaRequests::class);

    $request = Request::create('/', 'GET');
    $request->cookies->set('sidebar_state', 'true');

    $shared = $middleware->share($request);

    expect($shared['sidebarOpen'])->toBeTrue();
});

it('sets sidebarOpen to false when cookie is false', function (): void {
    $middleware = $this->app->make(HandleInertiaRequests::class);

    $request = Request::create('/', 'GET');
    $request->cookies->set('sidebar_state', 'false');

    $shared = $middleware->share($request);

    expect($shared['sidebarOpen'])->toBeFalse();
});

it('includes parent shared data', function (): void {
    $middleware = $this->app->make(HandleInertiaRequests::class);

    $request = Request::create('/', 'GET');

    $shared = $middleware->share($request);

    // Parent Inertia middleware shares 'errors' by default
    expect($shared)->toHaveKey('errors');
});

it('shares an empty nav for guests', function (): void {
    $middleware = $this->app->make(HandleInertiaRequests::class);

    $request = Request::create('/', 'GET');

    $shared = $middleware->share($request);

    expect($shared['nav'])->toBe([]);
});

it('shares the admin nav items with panel users', function (): void {
    Route::get('admin/example', fn (): string => 'example')->name('admin.example.index');
    Route::getRoutes()->refreshNameLookups();
    Feature::define('module:example', fn (): bool => true);

    $this->app->make(NavRegistry::class)->add(
        module: 'example',
        label: 'Example',
        routeName: 'admin.example.index',
        icon: 'boxes',
        group: 'Modules',
        sort: 1,
    );

    $user = User::factory()->create();
    $user->assignRole(Role::findOrCreate(User::PANEL_ROLE));

    $middleware = $this->app->make(HandleInertiaRequests::class);

    $request = Request::create('/', 'GET');
    $request->setUserResolver(fn (): User => $user);

    $shared = $middleware->share($request);

    expect($shared['nav'])->toHaveCount(1)
        ->and($shared['nav'][0]->toArray())->toBe([
            'label' => 'Example',
            'routeName' => 'admin.example.index',
            'href' => route('admin.example.index'),
            'icon' => 'boxes',
            'group' => 'Modules',
            'sort' => 1,
            'external' => false,
        ]);
});

it('shares no impersonation state by default', function (): void {
    $middleware = $this->app->make(HandleInertiaRequests::class);

    $request = Request::create('/', 'GET');

    $shared = $middleware->share($request);

    expect($shared['impersonation'])->toBeNull();
});

it('shares the impersonator name while impersonating', function (): void {
    $admin = User::factory()->create(['name' => 'Admin User']);
    $target = User::factory()->create();

    session()->put('impersonated_by', $admin->id);

    $middleware = $this->app->make(HandleInertiaRequests::class);

    $request = Request::create('/', 'GET');
    $request->setUserResolver(fn (): User => $target);

    $shared = $middleware->share($request);

    expect($shared['impersonation']?->toArray())->toBe(['impersonator' => 'Admin User']);
});

it('shares only the member nav items with users without panel access', function (): void {
    Route::get('admin/example', fn (): string => 'example')->name('admin.example.index');
    Route::get('example', fn (): string => 'example')->name('example.home');
    Route::getRoutes()->refreshNameLookups();
    Feature::define('module:example', fn (): bool => true);

    $nav = $this->app->make(NavRegistry::class);
    $nav->add(module: 'example', label: 'Example', routeName: 'admin.example.index');
    $nav->add(module: 'example', label: 'Example home', routeName: 'example.home', area: Area::Member);

    $user = User::factory()->create();

    $request = Request::create('/', 'GET');
    $request->setUserResolver(fn (): User => $user);

    $labels = array_map(
        static fn (NavItemData $item): string => $item->label,
        $this->app->make(HandleInertiaRequests::class)->share($request)['nav'],
    );

    expect($labels)->toContain('Example home')
        ->not->toContain('Example');
});
