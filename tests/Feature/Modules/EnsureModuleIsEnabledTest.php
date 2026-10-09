<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\ModuleSwitch;
use Illuminate\Support\Facades\DB;
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

it('reads every module flag in one query', function (): void {
    config(['pennant.default' => 'database']);
    Feature::forgetDrivers();
    Feature::define('module:first', true);
    Feature::define('module:second', true);
    $user = User::factory()->create();

    DB::enableQueryLog();
    $isOn = ModuleSwitch::isOn('first', $user) && ModuleSwitch::isOn('second', $user);

    $flagReads = collect(DB::getQueryLog())->filter(
        fn (array $query): bool => str_starts_with((string) $query['query'], 'select') && str_contains((string) $query['query'], '"features"'),
    );

    expect($isOn)->toBeTrue()
        ->and($flagReads)->toHaveCount(1);
});
