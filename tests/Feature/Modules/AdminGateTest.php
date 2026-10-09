<?php

declare(strict_types=1);

use App\Enums\Area;
use App\Models\User;
use Illuminate\Routing\Route as IlluminateRoute;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;
use Tests\Fixtures\Modules\TestModule\Providers\TestModuleServiceProvider;

beforeEach(function (): void {
    $this->app->register(TestModuleServiceProvider::class);

    Role::findOrCreate('admin');
    Role::findOrCreate('super-admin');
});

it('redirects guests to the login page', function (): void {
    $response = $this->get('/admin/test-module');

    $response->assertRedirectToRoute('login');
});

it('redirects unverified admins to email verification', function (): void {
    $user = User::factory()->unverified()->create();
    $user->assignRole('admin');

    $response = $this->actingAs($user)->get('/admin/test-module');

    $response->assertRedirectToRoute('verification.notice');
});

it('forbids authenticated users without the admin role', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/admin/test-module');

    $response->assertForbidden();
});

it('allows users with the admin role', function (): void {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $response = $this->actingAs($user)->get('/admin/test-module');

    $response->assertOk()
        ->assertJson(['module' => 'test-module']);
});

it('allows super admins holding the admin role', function (): void {
    $user = User::factory()->create();
    $user->assignRole(['admin', 'super-admin']);

    $response = $this->actingAs($user)->get('/admin/test-module');

    $response->assertOk();
});

it('grants super admins every declared permission without assigning it', function (): void {
    $user = User::factory()->create();
    $user->assignRole('super-admin');

    expect($user->can('test-module.view'))->toBeTrue();
});

it('still runs gates that are not permissions for super admins', function (): void {
    Gate::define('arbitrary-ability', fn (User $user): bool => false);

    $user = User::factory()->create();
    $user->assignRole('super-admin');

    expect($user->can('arbitrary-ability'))->toBeFalse();
});

it('does not let regular users pass denied gate checks', function (): void {
    Gate::define('arbitrary-ability', fn (User $user): bool => false);

    $user = User::factory()->create();
    $user->assignRole('admin');

    expect($user->can('arbitrary-ability'))->toBeFalse();
});

it('lets a project redefine who enters the panel through the one gate', function (): void {
    Gate::define(User::PANEL_ABILITY, fn (User $user): bool => $user->email === 'insider@example.com');

    $insider = User::factory()->create(['email' => 'insider@example.com']);
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($insider)->get('/admin/test-module')->assertOk();
    $this->actingAs($admin)->get('/admin/test-module')->assertForbidden();

    expect(Area::for($insider))->toBe(Area::Admin)
        ->and(Area::for($admin))->toBe(Area::Member);
});

it('guards every admin route with the panel gate, never role middleware', function (): void {
    $adminRoutes = collect(Route::getRoutes()->getRoutes())
        ->filter(fn (IlluminateRoute $route): bool => str_starts_with($route->uri(), 'admin/'));

    expect($adminRoutes)->not->toBeEmpty();

    $adminRoutes->each(function (IlluminateRoute $route): void {
        $middleware = $route->gatherMiddleware();

        expect($middleware)->toContain('can:'.User::PANEL_ABILITY)
            ->and(collect($middleware)->filter(fn (string $name): bool => str_starts_with($name, 'role:') || str_starts_with($name, 'permission:'))->all())->toBe([]);
    });
});
