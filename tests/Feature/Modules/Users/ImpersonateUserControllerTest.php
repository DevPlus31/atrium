<?php

declare(strict_types=1);

use App\Models\User;
use Lab404\Impersonate\Services\ImpersonateManager;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    $this->artisan('admin:sync-permissions')->assertSuccessful();
});

function usersImpersonateAdmin(): User
{
    $user = User::factory()->create();
    $user->assignRole('admin');

    return $user;
}

it('redirects guests to the login page', function (): void {
    $target = User::factory()->create();

    $response = $this->post('/admin/users/'.$target->id.'/impersonate');

    $response->assertRedirectToRoute('login');
});

it('forbids authenticated users without the admin role', function (): void {
    $user = User::factory()->create();
    $target = User::factory()->create();

    $response = $this->actingAs($user)->post(route('admin.users.impersonate', $target));

    $response->assertForbidden();
});

it('forbids admins without the users.impersonate permission', function (): void {
    Role::findByName('admin')->revokePermissionTo('users.impersonate');
    $target = User::factory()->create();

    $response = $this->actingAs(usersImpersonateAdmin())->post(route('admin.users.impersonate', $target));

    $response->assertForbidden();
});

it('impersonates a plain user and redirects to the dashboard', function (): void {
    $admin = usersImpersonateAdmin();
    $target = User::factory()->create(['name' => 'Jane Doe']);

    $response = $this->actingAs($admin)->post(route('admin.users.impersonate', $target));

    $response->assertRedirectToRoute('admin.dashboard.index')
        ->assertSessionHas('success', 'Now impersonating Jane Doe.');

    $this->assertAuthenticatedAs($target);

    $manager = $this->app->make(ImpersonateManager::class);

    expect($manager->isImpersonating())->toBeTrue()
        ->and($manager->getImpersonatorId())->toBe($admin->id);
});

it('logs the impersonation activity', function (): void {
    $admin = usersImpersonateAdmin();
    $target = User::factory()->create();

    $this->actingAs($admin)
        ->post(route('admin.users.impersonate', $target))
        ->assertRedirectToRoute('admin.dashboard.index');

    $activity = Activity::query()->where('event', 'impersonated')->sole();

    expect($activity->causer_id)->toBe($admin->id)
        ->and($activity->subject_id)->toBe($target->id);
});

it('forbids impersonating a user who can impersonate', function (): void {
    $admin = usersImpersonateAdmin();
    $target = usersImpersonateAdmin();

    $response = $this->actingAs($admin)->post(route('admin.users.impersonate', $target));

    $response->assertForbidden();
});

it('forbids impersonating yourself', function (): void {
    $admin = usersImpersonateAdmin();

    $response = $this->actingAs($admin)->post(route('admin.users.impersonate', $admin));

    $response->assertForbidden();
});

it('forbids starting a new impersonation while already impersonating', function (): void {
    $admin = usersImpersonateAdmin();
    $other = User::factory()->create();
    $target = User::factory()->create();

    $response = $this->actingAs($admin)
        ->withSession(['impersonated_by' => $other->id])
        ->post(route('admin.users.impersonate', $target));

    $response->assertForbidden();
});

it('redirects back with an error when impersonation fails', function (): void {
    $admin = usersImpersonateAdmin();
    $target = User::factory()->create(['name' => 'Jane Doe']);

    $manager = $this->mock(ImpersonateManager::class);
    $manager->shouldReceive('isImpersonating')->andReturnFalse();
    $manager->shouldReceive('take')->once()->andReturnFalse();

    $response = $this->actingAs($admin)
        ->from(route('admin.users.index'))
        ->post(route('admin.users.impersonate', $target));

    $response->assertRedirectToRoute('admin.users.index')
        ->assertSessionHas('error', 'Unable to impersonate Jane Doe.');

    $this->assertAuthenticatedAs($admin);
});
