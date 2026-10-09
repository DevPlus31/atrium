<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Modules\Users\Infrastructure\Models\Invitation;

beforeEach(function (): void {
    $this->artisan('admin:sync-permissions')->assertSuccessful();
});

it('invites someone from the users page and revokes the invitation', function (): void {
    Notification::fake();
    $this->actingAs(adminUser());

    visit(route('admin.users.index'))
        ->click('Invitations')
        ->assertPathIs('/admin/users/invitations')
        ->assertSee('No pending invitations.')
        ->type('#email', 'grace@example.com')
        ->click('[data-test="send-invitation"]')
        ->assertSee('Invitation sent.')
        ->assertSee('grace@example.com')
        ->click('Revoke')
        ->click('[role="alertdialog"] button:has-text("Revoke")')
        ->assertSee('Invitation revoked.')
        ->assertSee('No pending invitations.')
        ->assertNoJavaScriptErrors();

    expect(Invitation::query()->exists())->toBeFalse();
});

it('creates an account from the invitation link', function (): void {
    $invitation = Invitation::factory()->create(['email' => 'grace@example.com']);

    visit($invitation->acceptUrl())
        ->assertSee('Accept your invitation')
        ->assertValue('#email', 'grace@example.com')
        ->type('#name', 'Grace Hopper')
        ->type('#password', 'correct horse battery staple')
        ->type('#password_confirmation', 'correct horse battery staple')
        ->click('[data-test="accept-invitation-button"]')
        ->assertSee('Welcome to')
        ->assertNoJavaScriptErrors();

    expect(User::query()->where('email', 'grace@example.com')->sole()->hasVerifiedEmail())->toBeTrue();
});

it('explains an expired link', function (): void {
    $invitation = Invitation::factory()->expired()->create();

    visit($invitation->acceptUrl())
        ->assertSee('Invitation unavailable')
        ->assertSee('This invitation has expired.')
        ->assertNoJavaScriptErrors();
});
