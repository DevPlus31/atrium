<?php

declare(strict_types=1);

use App\Models\User;
use Modules\Users\Infrastructure\Models\Invitation;

it('is pending until it expires or is accepted', function (): void {
    expect(Invitation::factory()->create()->isPending())->toBeTrue()
        ->and(Invitation::factory()->expired()->create()->isPending())->toBeFalse()
        ->and(Invitation::factory()->accepted()->create()->isPending())->toBeFalse();
});

it('finds pending invitations', function (): void {
    $pending = Invitation::factory()->create();
    Invitation::factory()->expired()->create();
    Invitation::factory()->accepted()->create();

    expect(Invitation::pending()->pluck('id')->all())->toBe([$pending->id]);
});

it('knows who sent it, and forgets when they are deleted', function (): void {
    $inviter = User::factory()->create();
    $invitation = Invitation::factory()->create(['invited_by' => $inviter->id]);

    expect($invitation->inviter?->is($inviter))->toBeTrue();

    $inviter->delete();

    expect($invitation->refresh()->invited_by)->toBeNull();
});

it('links to a signed accept page', function (): void {
    $invitation = Invitation::factory()->create();

    expect($invitation->acceptUrl())
        ->toStartWith(route('invitations.show', $invitation))
        ->toContain('signature=');
});
