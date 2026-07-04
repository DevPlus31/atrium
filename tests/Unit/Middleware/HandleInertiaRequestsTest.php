<?php

declare(strict_types=1);

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\User;
use App\Modules\NavRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Pennant\Feature;

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

it('shares nav items for authenticated users', function (): void {
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
        ]);
});

it('shares null flash messages when the request has no session', function (): void {
    $middleware = $this->app->make(HandleInertiaRequests::class);

    $request = Request::create('/', 'GET');

    $shared = $middleware->share($request);

    expect($shared['flash'])->toBe([
        'success' => null,
        'error' => null,
    ]);
});

it('shares flash messages from the session', function (): void {
    $middleware = $this->app->make(HandleInertiaRequests::class);

    $request = Request::create('/', 'GET');
    $request->setLaravelSession($this->app->make('session.store'));
    $request->session()->put('success', 'Saved.');
    $request->session()->put('error', 'Failed.');

    $shared = $middleware->share($request);

    expect($shared['flash'])->toBe([
        'success' => 'Saved.',
        'error' => 'Failed.',
    ]);
});
