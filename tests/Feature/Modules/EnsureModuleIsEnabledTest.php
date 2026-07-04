<?php

declare(strict_types=1);

use App\Models\User;
use Laravel\Pennant\Feature;
use Spatie\Permission\Models\Role;
use Tests\Fixtures\Modules\TestModule\Providers\TestModuleServiceProvider;

beforeEach(function (): void {
    $this->app->register(TestModuleServiceProvider::class);

    Role::findOrCreate('admin');
});

it('allows access while the module feature is active', function (): void {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $response = $this->actingAs($user)->get('/admin/test-module');

    $response->assertOk();
});

it('returns a 404 when the module feature is inactive for the user', function (): void {
    $user = User::factory()->create();
    $user->assignRole('admin');

    Feature::for($user)->deactivate('module:test-module');

    $response = $this->actingAs($user)->get('/admin/test-module');

    $response->assertNotFound();
});

it('does not affect other users when disabled for one user', function (): void {
    $disabled = User::factory()->create();
    $disabled->assignRole('admin');

    $enabled = User::factory()->create();
    $enabled->assignRole('admin');

    Feature::for($disabled)->deactivate('module:test-module');

    $this->actingAs($enabled)->get('/admin/test-module')->assertOk();
});
