<?php

declare(strict_types=1);

namespace Modules\Users\Infrastructure\Repositories;

use Modules\Users\Domain\Repositories\InvitationRepository;
use Modules\Users\Infrastructure\Models\Invitation;

final readonly class EloquentInvitationRepository implements InvitationRepository
{
    public function lockForUpdate(Invitation $invitation): Invitation
    {
        return Invitation::query()->whereKey($invitation->getKey())->lockForUpdate()->firstOrFail();
    }

    public function save(Invitation $invitation): void
    {
        $invitation->save();
    }

    public function delete(Invitation $invitation): void
    {
        $invitation->delete();
    }
}
