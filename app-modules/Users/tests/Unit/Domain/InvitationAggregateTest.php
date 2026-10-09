<?php

declare(strict_types=1);

use App\Models\User;
use Modules\Users\Domain\Events\InvitationAccepted;
use Modules\Users\Domain\Events\InvitationSent;
use Modules\Users\Domain\Exceptions\InvitationAlreadyAccepted;
use Modules\Users\Domain\Exceptions\InvitationNotPending;
use Modules\Users\Domain\ValueObjects\Email;
use Modules\Users\Infrastructure\Models\Invitation;

it('issues a pending invitation and records that it was sent', function (): void {
    $inviter = User::factory()->create();

    $invitation = Invitation::issue(new Email(' Grace@Example.com '), ['admin'], $inviter);

    expect($invitation->email)->toBe('grace@example.com')
        ->and($invitation->roles)->toBe(['admin'])
        ->and($invitation->invited_by)->toBe($inviter->id)
        ->and($invitation->expires_at->toIso8601String())->toBe(now()->addDays(Invitation::LIFETIME_DAYS)->toIso8601String())
        ->and($invitation->isPending())->toBeTrue()
        ->and($invitation->releaseDomainEvents())->toEqual([new InvitationSent($invitation->id)]);
});

it('renews an invitation, even an expired one', function (): void {
    $invitation = Invitation::factory()->expired()->create();

    $invitation->renew();

    expect($invitation->isPending())->toBeTrue()
        ->and($invitation->releaseDomainEvents())->toEqual([new InvitationSent($invitation->id)]);
});

it('cannot renew an accepted invitation', function (): void {
    $invitation = Invitation::factory()->accepted()->create(['email' => 'grace@example.com']);

    expect(fn () => $invitation->renew())
        ->toThrow(InvitationAlreadyAccepted::class, 'The invitation for [grace@example.com] was already accepted.');
});

it('is accepted once, by the new account', function (): void {
    $invitation = Invitation::factory()->create(['invited_by' => null]);
    $user = User::factory()->create(['name' => 'Grace']);

    $invitation->accept($user);

    expect($invitation->accepted_at)->not->toBeNull()
        ->and($invitation->releaseDomainEvents())->toEqual([new InvitationAccepted($invitation->id, null, $user->id, 'Grace')]);

    expect(fn () => $invitation->accept($user))
        ->toThrow(InvitationNotPending::class, sprintf('The invitation for [%s] was already used or has expired.', $invitation->email));
});

it('cannot be accepted once expired', function (): void {
    $invitation = Invitation::factory()->expired()->create();

    expect(fn () => $invitation->accept(User::factory()->create()))->toThrow(InvitationNotPending::class);
});

it('can be revoked until it is accepted', function (): void {
    expect(fn () => Invitation::factory()->expired()->create()->revoke())->not->toThrow(InvitationAlreadyAccepted::class)
        ->and(fn () => Invitation::factory()->accepted()->create()->revoke())->toThrow(InvitationAlreadyAccepted::class);
});
