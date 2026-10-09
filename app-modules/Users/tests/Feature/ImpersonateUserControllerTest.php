<?php

declare(strict_types=1);

use App\Models\User;
use Inertia\Support\SessionKey;
use Inertia\Testing\AssertableInertia;
use Lab404\Impersonate\Services\ImpersonateManager;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    $this->artisan('admin:sync-permissions')->assertSuccessful();
});

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

    $response = $this->actingAs(adminUser())->post(route('admin.users.impersonate', $target));

    $response->assertForbidden();
});

it('impersonates a plain user and lands on a page they can open', function (): void {
    $admin = adminUser();
    $target = User::factory()->create(['name' => 'Jane Doe']);

    $response = $this->actingAs($admin)->post(route('admin.users.impersonate', $target));

    $response->assertRedirectToRoute('user-profile.edit')
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Now impersonating Jane Doe.'])
        ->assertSessionHas(SessionKey::CLEAR_HISTORY, true);

    $this->get(route('user-profile.edit'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page->where('impersonation.impersonator', $admin->name));

    $this->assertAuthenticatedAs($target);

    $manager = $this->app->make(ImpersonateManager::class);

    expect($manager->isImpersonating())->toBeTrue()
        ->and($manager->getImpersonatorId())->toBe($admin->id);
});

it('logs the impersonation activity', function (): void {
    $admin = adminUser();
    $target = User::factory()->create();

    $this->actingAs($admin)
        ->post(route('admin.users.impersonate', $target))
        ->assertRedirectToRoute('user-profile.edit');

    $activity = Activity::query()->where('event', 'impersonated')->sole();

    expect($activity->causer_id)->toBe($admin->id)
        ->and($activity->subject_id)->toBe($target->id);
});

it('forbids impersonating a user who can impersonate', function (): void {
    $admin = adminUser();
    $target = adminUser();

    $response = $this->actingAs($admin)->post(route('admin.users.impersonate', $target));

    $response->assertForbidden();
});

it('forbids impersonating yourself', function (): void {
    $admin = adminUser();

    $response = $this->actingAs($admin)->post(route('admin.users.impersonate', $admin));

    $response->assertForbidden();
});

it('forbids starting a new impersonation while already impersonating', function (): void {
    $admin = adminUser();
    $other = User::factory()->create();
    $target = User::factory()->create();

    $response = $this->actingAs($admin)
        ->withSession([$this->app->make(ImpersonateManager::class)->getSessionKey() => $other->id])
        ->post(route('admin.users.impersonate', $target));

    $response->assertForbidden();
});

it('redirects back with an error when impersonation fails', function (): void {
    $admin = adminUser();
    $target = User::factory()->create(['name' => 'Jane Doe']);

    $this->partialMock(ImpersonateManager::class)->shouldReceive('take')->once()->andReturnFalse();

    $response = $this->actingAs($admin)
        ->from(route('admin.users.index'))
        ->post(route('admin.users.impersonate', $target));

    $response->assertRedirectToRoute('admin.users.index')
        ->assertInertiaFlash('toast', ['type' => 'error', 'message' => 'Unable to impersonate Jane Doe.']);

    $this->assertAuthenticatedAs($admin);
});

it('forbids impersonating a user who holds permissions the admin lacks', function (): void {
    Role::findOrCreate('auditor')->givePermissionTo('audit.view');
    Role::findByName('admin')->revokePermissionTo('audit.view');
    $admin = adminUser();
    $target = User::factory()->create();
    $target->assignRole('auditor');

    $this->actingAs($admin)->post(route('admin.users.impersonate', $target))->assertForbidden();

    expect($admin->can('impersonate', $target))->toBeFalse();
});

it('lets a super-admin impersonate a regular user', function (): void {
    $superAdmin = superAdminUser();
    $target = User::factory()->create();

    $this->actingAs($superAdmin)
        ->post(route('admin.users.impersonate', $target))
        ->assertRedirectToRoute('user-profile.edit');

    $this->assertAuthenticatedAs($target);

    expect(Activity::query()->where('event', 'impersonated')->sole()->causer_id)->toBe($superAdmin->id);
});

it('forbids impersonating a user granted a permission directly that the admin lacks', function (): void {
    Role::findByName('admin')->revokePermissionTo('audit.view');
    $admin = adminUser();
    $target = User::factory()->create();
    $target->givePermissionTo('audit.view');

    $this->actingAs($admin)->post(route('admin.users.impersonate', $target))->assertForbidden();

    $this->assertAuthenticatedAs($admin);
});
