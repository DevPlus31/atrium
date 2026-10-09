<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Modules\Users\Actions\AcceptInvitation;
use Modules\Users\Actions\InviteUser;
use Modules\Users\Actions\ResendInvitation;
use Modules\Users\Actions\RevokeInvitation;
use Modules\Users\Domain\Exceptions\AccountAlreadyExists;
use Modules\Users\Domain\Exceptions\InvitationAlreadyAccepted;
use Modules\Users\Domain\Exceptions\InvitationAlreadyPending;
use Modules\Users\Domain\Exceptions\InvitationNotPending;
use Modules\Users\Infrastructure\Models\Invitation;
use Modules\Users\Notifications\InvitationAcceptedNotification;
use Modules\Users\Notifications\InvitationNotification;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

it('invites someone and emails the link', function (): void {
    Notification::fake();
    $inviter = User::factory()->create();

    $invitation = resolve(InviteUser::class)->handle($inviter, ' Grace@Example.com ', ['admin']);

    expect($invitation->email)->toBe('grace@example.com')
        ->and($invitation->roles)->toBe(['admin'])
        ->and($invitation->invited_by)->toBe($inviter->id)
        ->and($invitation->isPending())->toBeTrue()
        ->and(Activity::query()->where('event', 'invited')->sole()->subject_id)->toBe($invitation->id);

    Notification::assertSentOnDemand(
        InvitationNotification::class,
        fn (InvitationNotification $notification, array $channels, AnonymousNotifiable $notifiable): bool => $notifiable->routes['mail'] === 'grace@example.com',
    );
});

it('resends an invitation with a fresh expiry', function (): void {
    Notification::fake();
    $invitation = Invitation::factory()->create(['expires_at' => now()->addHour()]);

    resolve(ResendInvitation::class)->handle($invitation);

    expect($invitation->refresh()->expires_at->toIso8601String())->toBe(now()->addDays(Invitation::LIFETIME_DAYS)->toIso8601String());

    Notification::assertSentOnDemandTimes(InvitationNotification::class, 1);
});

it('revokes an invitation', function (): void {
    $invitation = Invitation::factory()->create();

    resolve(RevokeInvitation::class)->handle($invitation);

    expect(Invitation::query()->exists())->toBeFalse()
        ->and(Activity::query()->where('event', 'invitation-revoked')->exists())->toBeTrue();
});

it('creates the verified account with the roles that still exist and tells the inviter', function (): void {
    Notification::fake();
    $this->artisan('admin:sync-permissions')->assertSuccessful();
    Role::findOrCreate('editor');
    $inviter = adminUser();
    $invitation = Invitation::factory()->create([
        'email' => 'grace@example.com',
        'roles' => ['editor', 'deleted-role'],
        'invited_by' => $inviter->id,
    ]);

    $user = resolve(AcceptInvitation::class)->handle($invitation, 'Grace Hopper', 'correct horse battery staple');

    expect($user->email)->toBe('grace@example.com')
        ->and($user->hasVerifiedEmail())->toBeTrue()
        ->and($user->getRoleNames()->all())->toBe(['editor'])
        ->and($invitation->refresh()->accepted_at)->not->toBeNull();

    Notification::assertSentTo($inviter, InvitationAcceptedNotification::class);
});

it('accepts an invitation only once, creating no second account', function (): void {
    $invitation = Invitation::factory()->create(['email' => 'grace@example.com']);
    $stale = Invitation::query()->findOrFail($invitation->id);

    resolve(AcceptInvitation::class)->handle($invitation, 'Grace Hopper', 'correct horse battery staple');

    // A second request still holding the old copy is caught under the lock.
    expect(fn () => resolve(AcceptInvitation::class)->handle($stale, 'Grace Again', 'correct horse battery staple'))
        ->toThrow(InvitationNotPending::class);

    expect(User::query()->where('email', 'grace@example.com')->count())->toBe(1);
});

it('cannot resend an accepted invitation', function (): void {
    $invitation = Invitation::factory()->accepted()->create();

    expect(fn () => resolve(ResendInvitation::class)->handle($invitation))->toThrow(InvitationAlreadyAccepted::class);
});

it('grants only the roles the inviter may still grant', function (): void {
    $this->artisan('admin:sync-permissions')->assertSuccessful();
    Role::findOrCreate(User::SUPER_ADMIN_ROLE);
    $inviter = adminUser();
    $invitation = Invitation::factory()->create([
        'roles' => [User::PANEL_ROLE, User::SUPER_ADMIN_ROLE],
        'invited_by' => $inviter->id,
    ]);

    $user = resolve(AcceptInvitation::class)->handle($invitation, 'Grace', 'correct horse battery staple');

    expect($user->getRoleNames()->all())->toBe([User::PANEL_ROLE]);
});

it('grants no role once the inviter may no longer invite, or is gone', function (bool $inviterRemains): void {
    $this->artisan('admin:sync-permissions')->assertSuccessful();
    $inviter = User::factory()->create();
    $invitation = Invitation::factory()->create([
        'roles' => [User::PANEL_ROLE],
        'invited_by' => $inviterRemains ? $inviter->id : null,
    ]);

    $user = resolve(AcceptInvitation::class)->handle($invitation, 'Grace', 'correct horse battery staple');

    expect($user->getRoleNames()->all())->toBe([]);
})->with(['inviter lost the right' => true, 'inviter deleted' => false]);

it('refuses to accept for an address that got an account meanwhile', function (): void {
    $invitation = Invitation::factory()->create(['email' => 'grace@example.com']);
    User::factory()->create(['email' => 'grace@example.com']);

    expect(fn () => resolve(AcceptInvitation::class)->handle($invitation, 'Grace', 'correct horse battery staple'))
        ->toThrow(AccountAlreadyExists::class, 'An account with the email [grace@example.com] already exists.');

    expect($invitation->refresh()->accepted_at)->toBeNull();
});

it('refuses to invite an existing account or invite twice', function (): void {
    Notification::fake();
    $inviter = User::factory()->create();
    User::factory()->create(['email' => 'member@example.com']);
    Invitation::factory()->create(['email' => 'pending@example.com']);
    $action = resolve(InviteUser::class);

    expect(fn () => $action->handle($inviter, 'member@example.com', []))->toThrow(AccountAlreadyExists::class)
        ->and(fn () => $action->handle($inviter, 'pending@example.com', []))->toThrow(InvitationAlreadyPending::class, '[pending@example.com] already has a pending invitation.');

    Notification::assertNothingSent();
});
