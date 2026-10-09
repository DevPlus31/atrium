<?php

declare(strict_types=1);

namespace Modules\Users\Listeners;

use App\Models\User;
use Modules\Users\Domain\Events\InvitationAccepted;
use Modules\Users\Notifications\InvitationAcceptedNotification;

/**
 * Tells the inviter, if they still have an account, that the invitee joined.
 */
final readonly class NotifyInviterOfAcceptance
{
    public function handle(InvitationAccepted $event): void
    {
        if ($event->inviterId === null) {
            return;
        }

        User::query()->find($event->inviterId)
            ?->notify(new InvitationAcceptedNotification($event->userId, $event->userName));
    }
}
