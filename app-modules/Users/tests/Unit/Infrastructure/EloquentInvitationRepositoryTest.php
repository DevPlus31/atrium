<?php

declare(strict_types=1);

use Modules\Users\Domain\Repositories\InvitationRepository;
use Modules\Users\Infrastructure\Models\Invitation;
use Modules\Users\Infrastructure\Repositories\EloquentInvitationRepository;

it('is the bound invitation repository', function (): void {
    expect(resolve(InvitationRepository::class))->toBeInstanceOf(EloquentInvitationRepository::class);
});

it('saves, re-reads and deletes invitations', function (): void {
    $invitations = resolve(InvitationRepository::class);
    $invitation = Invitation::factory()->make();

    $invitations->save($invitation);

    expect($invitations->lockForUpdate($invitation)->is($invitation))->toBeTrue();

    $invitations->delete($invitation);

    expect(Invitation::query()->exists())->toBeFalse();
});
