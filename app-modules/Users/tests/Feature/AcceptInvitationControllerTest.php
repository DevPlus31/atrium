<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Inertia\Testing\AssertableInertia;
use Modules\Users\Infrastructure\Models\Invitation;
use Modules\Users\Notifications\InvitationAcceptedNotification;

it('only opens from the signed link', function (): void {
    $invitation = Invitation::factory()->create();

    $this->get(route('invitations.show', $invitation))->assertForbidden();
    $this->post(route('invitations.store', $invitation))->assertForbidden();
});

it('shows the form for a pending invitation', function (): void {
    $invitation = Invitation::factory()->create(['email' => 'new@example.com']);

    $this->get($invitation->acceptUrl())
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('users::public/accept-invitation')
            ->where('email', 'new@example.com')
            ->where('status', 'pending')
            ->where('submitUrl', $invitation->acceptUrl()));
});

it('explains why an invitation cannot be used', function (Closure $invitation, string $status): void {
    $invitation = $invitation();

    $this->get($invitation->acceptUrl())
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page->where('status', $status));
})->with([
    'accepted' => [fn (): Invitation => Invitation::factory()->accepted()->create(), 'accepted'],
    'expired' => [fn (): Invitation => Invitation::factory()->expired()->create(), 'expired'],
    'already registered' => [function (): Invitation {
        User::factory()->create(['email' => 'taken@example.com']);

        return Invitation::factory()->create(['email' => 'taken@example.com']);
    }, 'registered'],
]);

it('is a 404 once revoked', function (): void {
    $invitation = Invitation::factory()->create();
    $url = $invitation->acceptUrl();
    $invitation->delete();

    $this->get($url)->assertNotFound();
});

it('sends signed-in users away', function (): void {
    $invitation = Invitation::factory()->create();

    $this->actingAs(User::factory()->create())
        ->get($invitation->acceptUrl())
        ->assertRedirect();
});

it('creates the verified account with the invited roles and signs it in', function (): void {
    $this->artisan('admin:sync-permissions')->assertSuccessful();
    $inviter = adminUser();
    $invitation = Invitation::factory()->create([
        'email' => 'new@example.com',
        'roles' => ['admin', 'role-deleted-since'],
        'invited_by' => $inviter->id,
    ]);

    $this->post($invitation->acceptUrl(), [
        'name' => 'Grace Hopper',
        'password' => 'correct horse battery staple',
        'password_confirmation' => 'correct horse battery staple',
    ])
        ->assertRedirectToRoute('dashboard')
        ->assertToast('Welcome to '.config('app.name').'!');

    $user = User::query()->where('email', 'new@example.com')->sole();

    $this->assertAuthenticatedAs($user);

    expect($user->name)->toBe('Grace Hopper')
        ->and($user->hasVerifiedEmail())->toBeTrue()
        ->and($user->getRoleNames()->all())->toBe(['admin'])
        ->and($invitation->refresh()->accepted_at)->not->toBeNull();

    $notice = DatabaseNotification::query()->sole();

    expect($notice->notifiable_id)->toBe($inviter->id)
        ->and($notice->type)->toBe(InvitationAcceptedNotification::class)
        ->and($notice->data['title'])->toBe('Grace Hopper accepted your invitation')
        ->and($notice->data['url'])->toBe(route('admin.users.edit', $user));
});

it('works when the inviter has since been deleted', function (): void {
    $invitation = Invitation::factory()->create(['email' => 'new@example.com']);

    $this->post($invitation->acceptUrl(), [
        'name' => 'Grace Hopper',
        'password' => 'correct horse battery staple',
        'password_confirmation' => 'correct horse battery staple',
    ])->assertRedirectToRoute('dashboard');

    expect(User::query()->where('email', 'new@example.com')->exists())->toBeTrue()
        ->and(DatabaseNotification::query()->exists())->toBeFalse();
});

it('refuses an invitation that can no longer be used', function (Closure $invitation, string $message): void {
    $invitation = $invitation();

    $this->post($invitation->acceptUrl(), [
        'name' => 'Grace Hopper',
        'password' => 'correct horse battery staple',
        'password_confirmation' => 'correct horse battery staple',
    ])->assertSessionHasErrors(['invitation' => $message]);

    $this->assertGuest();
})->with([
    'expired' => [fn (): Invitation => Invitation::factory()->expired()->create(), 'This invitation is no longer valid.'],
    'accepted' => [fn (): Invitation => Invitation::factory()->accepted()->create(), 'This invitation is no longer valid.'],
    'already registered' => [function (): Invitation {
        User::factory()->create(['email' => 'taken@example.com']);

        return Invitation::factory()->create(['email' => 'taken@example.com']);
    }, 'An account with this email already exists. Sign in instead.'],
]);

it('validates the name and password', function (): void {
    $invitation = Invitation::factory()->create();

    $this->post($invitation->acceptUrl(), ['name' => '', 'password' => 'short', 'password_confirmation' => 'other'])
        ->assertSessionHasErrors(['name', 'password']);

    expect(User::query()->exists())->toBeFalse()
        ->and($invitation->refresh()->accepted_at)->toBeNull();
});

it('is not found while the Users module is switched off', function (): void {
    config(['modules.disabled' => ['users']]);
    $invitation = Invitation::factory()->create();

    $this->get($invitation->acceptUrl())->assertNotFound();
});
