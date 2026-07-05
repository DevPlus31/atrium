<?php

declare(strict_types=1);

use App\Models\User;
use Lab404\Impersonate\Services\ImpersonateManager;
use Spatie\Activitylog\Models\Activity;

beforeEach(function (): void {
    $this->artisan('admin:sync-permissions')->assertSuccessful();
});

function leaveImpersonationAdmin(): User
{
    $user = User::factory()->create();
    $user->assignRole('admin');

    return $user;
}

it('redirects guests to the login page', function (): void {
    $response = $this->post('/impersonation/leave');

    $response->assertRedirectToRoute('login');
});

it('forbids leaving when not impersonating', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('impersonation.leave'));

    $response->assertForbidden();
});

it('restores the impersonator and redirects to the users index', function (): void {
    $admin = leaveImpersonationAdmin();
    $target = User::factory()->create(['name' => 'Jane Doe']);

    $this->actingAs($admin)
        ->post(route('admin.users.impersonate', $target))
        ->assertRedirectToRoute('admin.dashboard.index');

    $response = $this->post(route('impersonation.leave'));

    $response->assertRedirectToRoute('admin.users.index')
        ->assertSessionHas('success', 'Stopped impersonating Jane Doe.');

    $this->assertAuthenticatedAs($admin);

    expect($this->app->make(ImpersonateManager::class)->isImpersonating())->toBeFalse();
});

it('logs the leave activity against the impersonated user', function (): void {
    $admin = leaveImpersonationAdmin();
    $target = User::factory()->create();

    $this->actingAs($admin)
        ->post(route('admin.users.impersonate', $target))
        ->assertRedirectToRoute('admin.dashboard.index');

    $this->post(route('impersonation.leave'))
        ->assertRedirectToRoute('admin.users.index');

    $activity = Activity::query()->where('event', 'impersonation-left')->sole();

    expect($activity->causer_id)->toBe($admin->id)
        ->and($activity->subject_id)->toBe($target->id);
});
