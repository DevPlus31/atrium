<?php

declare(strict_types=1);

use App\Models\User;
use Inertia\Support\SessionKey;
use Lab404\Impersonate\Services\ImpersonateManager;
use Spatie\Activitylog\Models\Activity;

beforeEach(function (): void {
    $this->artisan('admin:sync-permissions')->assertSuccessful();
});

it('redirects guests to the login page', function (): void {
    $response = $this->post('/impersonation/leave');

    $response->assertRedirectToRoute('login');
});

it('forbids leaving when not impersonating', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('impersonation.leave'));

    $response->assertForbidden();
});

it('restores the impersonator and sends them home', function (): void {
    $admin = adminUser();
    $target = User::factory()->create(['name' => 'Jane Doe']);

    $this->actingAs($admin);
    resolve(ImpersonateManager::class)->take($admin, $target);

    $response = $this->post(route('impersonation.leave'));

    $response->assertRedirectToRoute('dashboard')
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Stopped impersonating Jane Doe.'])
        ->assertSessionHas(SessionKey::CLEAR_HISTORY, true);

    $this->assertAuthenticatedAs($admin);

    expect($this->app->make(ImpersonateManager::class)->isImpersonating())->toBeFalse();
});

it('logs the leave activity against the impersonated user', function (): void {
    $admin = adminUser();
    $target = User::factory()->create();

    $this->actingAs($admin);
    resolve(ImpersonateManager::class)->take($admin, $target);

    $this->post(route('impersonation.leave'))
        ->assertRedirectToRoute('dashboard');

    $activity = Activity::query()->where('event', 'impersonation-left')->sole();

    expect($activity->causer_id)->toBe($admin->id)
        ->and($activity->subject_id)->toBe($target->id);
});
