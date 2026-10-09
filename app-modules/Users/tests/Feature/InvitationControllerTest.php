<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia;
use Modules\Users\Infrastructure\Models\Invitation;
use Modules\Users\Notifications\InvitationNotification;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    $this->withoutVite();

    $this->artisan('admin:sync-permissions')->assertSuccessful();
});

it('redirects guests to the login page', function (string $method, string $uri): void {
    $this->{$method}($uri)->assertRedirectToRoute('login');
})->with([
    'index' => ['get', '/admin/users/invitations'],
    'store' => ['post', '/admin/users/invitations'],
]);

it('needs the users.create permission', function (): void {
    Role::findByName('admin')->revokePermissionTo('users.create');
    $invitation = Invitation::factory()->create();
    $admin = adminUser();

    $this->actingAs($admin)->get(route('admin.users.invitations.index'))->assertForbidden();
    $this->actingAs($admin)->post(route('admin.users.invitations.store'), ['email' => 'new@example.com'])->assertForbidden();
    $this->actingAs($admin)->post(route('admin.users.invitations.resend', $invitation))->assertForbidden();
    $this->actingAs($admin)->delete(route('admin.users.invitations.destroy', $invitation))->assertForbidden();
});

it('lists pending invitations only, newest first', function (): void {
    $admin = adminUser(['name' => 'Ada']);
    Invitation::factory()->create(['email' => 'older@example.com', 'invited_by' => $admin->id, 'created_at' => now()->subDay()]);
    Invitation::factory()->create(['email' => 'newer@example.com', 'roles' => ['admin']]);
    Invitation::factory()->expired()->create();
    Invitation::factory()->accepted()->create();

    $this->actingAs($admin)->get(route('admin.users.invitations.index'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('users::invitations')
            ->has('invitations.data', 2)
            ->where('invitations.data.0.email', 'newer@example.com')
            ->where('invitations.data.0.roles', ['admin'])
            ->where('invitations.data.0.invited_by', null)
            ->where('invitations.data.1.invited_by', 'Ada')
            ->where('roles', fn (Collection $roles): bool => $roles->contains('admin'))
            ->where('lifetimeDays', Invitation::LIFETIME_DAYS));
});

it('invites someone and emails them the link', function (): void {
    Notification::fake();
    $admin = adminUser();

    $this->actingAs($admin)
        ->post(route('admin.users.invitations.store'), ['email' => 'new@example.com', 'roles' => ['admin']])
        ->assertRedirectToRoute('admin.users.invitations.index')
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Invitation sent.']);

    $invitation = Invitation::query()->sole();

    expect($invitation->email)->toBe('new@example.com')
        ->and($invitation->roles)->toBe(['admin'])
        ->and($invitation->invited_by)->toBe($admin->id)
        ->and($invitation->expires_at->toIso8601String())->toBe(now()->addDays(Invitation::LIFETIME_DAYS)->toIso8601String())
        ->and(Activity::query()->where('event', 'invited')->sole()->subject_id)->toBe($invitation->id);

    Notification::assertSentOnDemand(
        InvitationNotification::class,
        fn (InvitationNotification $notification, array $channels, AnonymousNotifiable $notifiable): bool => $notifiable->routes['mail'] === 'new@example.com'
            && $notification->invitation->is($invitation),
    );
});

it('refuses addresses that already have an account or a pending invitation', function (string $email): void {
    Notification::fake();
    User::factory()->create(['email' => 'member@example.com']);
    Invitation::factory()->create(['email' => 'pending@example.com']);
    Invitation::factory()->expired()->create(['email' => 'expired@example.com']);

    $this->actingAs(adminUser())
        ->post(route('admin.users.invitations.store'), ['email' => $email])
        ->assertSessionHasErrors(['email' => 'This person already has an account or a pending invitation.']);

    Notification::assertNothingSent();
})->with(['member@example.com', 'pending@example.com']);

it('can invite again once the old invitation expired', function (): void {
    Notification::fake();
    Invitation::factory()->expired()->create(['email' => 'expired@example.com']);

    $this->actingAs(adminUser())
        ->post(route('admin.users.invitations.store'), ['email' => 'expired@example.com'])
        ->assertSessionHasNoErrors();

    expect(Invitation::pending()->where('email', 'expired@example.com')->exists())->toBeTrue();
});

it('cannot invite into a role the admin cannot grant', function (): void {
    Role::findOrCreate(User::SUPER_ADMIN_ROLE);

    $this->actingAs(adminUser())
        ->post(route('admin.users.invitations.store'), ['email' => 'new@example.com', 'roles' => [User::SUPER_ADMIN_ROLE]])
        ->assertSessionHasErrors(['roles.0' => 'You cannot grant the super-admin role.']);

    expect(Invitation::query()->exists())->toBeFalse();
});

it('sends an invitation again with a fresh expiry', function (): void {
    Notification::fake();
    $invitation = Invitation::factory()->create(['email' => 'new@example.com', 'expires_at' => now()->addDay()]);

    $this->actingAs(adminUser())
        ->post(route('admin.users.invitations.resend', $invitation))
        ->assertRedirectToRoute('admin.users.invitations.index')
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Invitation sent again to new@example.com.']);

    expect($invitation->refresh()->expires_at->toIso8601String())->toBe(now()->addDays(Invitation::LIFETIME_DAYS)->toIso8601String())
        ->and(Activity::query()->where('event', 'invitation-resent')->exists())->toBeTrue();

    Notification::assertSentOnDemandTimes(InvitationNotification::class, 1);
});

it('does not resend an accepted invitation', function (): void {
    Notification::fake();
    $invitation = Invitation::factory()->accepted()->create();

    $this->actingAs(adminUser())
        ->from(route('admin.users.invitations.index'))
        ->post(route('admin.users.invitations.resend', $invitation))
        ->assertRedirectToRoute('admin.users.invitations.index')
        ->assertInertiaFlash('toast.type', 'error');

    Notification::assertNothingSent();
});

it('does not revoke an accepted invitation', function (): void {
    $invitation = Invitation::factory()->accepted()->create();

    $this->actingAs(adminUser())
        ->from(route('admin.users.invitations.index'))
        ->delete(route('admin.users.invitations.destroy', $invitation))
        ->assertRedirectToRoute('admin.users.invitations.index')
        ->assertInertiaFlash('toast.type', 'error');

    expect($invitation->fresh())->not->toBeNull();
});

it('revokes an invitation', function (): void {
    $invitation = Invitation::factory()->create();

    $this->actingAs(adminUser())
        ->delete(route('admin.users.invitations.destroy', $invitation))
        ->assertRedirectToRoute('admin.users.invitations.index')
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Invitation revoked.']);

    expect(Invitation::query()->exists())->toBeFalse()
        ->and(Activity::query()->where('event', 'invitation-revoked')->exists())->toBeTrue();
});
