<?php

declare(strict_types=1);

namespace Modules\Users\Listeners;

use Illuminate\Support\Facades\Notification;
use Modules\Users\Domain\Events\InvitationSent;
use Modules\Users\Infrastructure\Models\Invitation;
use Modules\Users\Notifications\InvitationNotification;

/**
 * Emails the link whenever an invitation is issued or renewed, in the
 * language of whoever sent it. Found by event discovery.
 */
final readonly class SendInvitationEmail
{
    public function handle(InvitationSent $event): void
    {
        $invitation = Invitation::query()->find($event->invitationId);

        if (! $invitation instanceof Invitation) {
            return;
        }

        Notification::route('mail', $invitation->email)
            ->notify(new InvitationNotification($invitation)->locale(app()->getLocale()));
    }
}
