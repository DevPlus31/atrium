<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Collection;
use Inertia\Testing\AssertableInertia;
use Laravel\Pennant\Feature;

beforeEach(function (): void {
    $this->withoutVite();

    $this->artisan('admin:sync-permissions')->assertSuccessful();
});

it('adds its widget to the member home and resolves it for the viewer', function (): void {
    $user = User::factory()->create();
    activity('users')->performedOn($user)->causedBy(adminUser(['name' => 'Ada Admin']))->event('updated')->log('updated');

    $this->actingAs($user)->get(route('member.dashboard'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->where('widgets', fn (Collection $widgets): bool => collect($widgets)->contains('key', 'audit.account-activity'))
            ->loadDeferredProps('widgets', fn (AssertableInertia $reloaded): AssertableInertia => $reloaded
                ->where('widget:audit_account-activity.entries.0.causer', 'Ada Admin')
                ->etc()));
});

it('leaves the member home when the module is off for the member', function (): void {
    $user = User::factory()->create();
    Feature::for($user)->deactivate('module:audit');

    $this->actingAs($user)->get(route('member.dashboard'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->where('widgets', fn (Collection $widgets): bool => collect($widgets)->doesntContain('key', 'audit.account-activity'))
            ->missing('widget:audit_account-activity'));
});
