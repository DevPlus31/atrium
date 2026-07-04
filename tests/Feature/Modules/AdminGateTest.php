<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Gate;
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

it('lets super admins pass arbitrary gate checks', function (): void {
    Gate::define('arbitrary-ability', fn (User $user): bool => false);

    $user = User::factory()->create();
    $user->assignRole('super-admin');

    expect($user->can('arbitrary-ability'))->toBeTrue();
});

it('does not let regular users pass denied gate checks', function (): void {
    Gate::define('arbitrary-ability', fn (User $user): bool => false);

    $user = User::factory()->create();
    $user->assignRole('admin');

    expect($user->can('arbitrary-ability'))->toBeFalse();
});
