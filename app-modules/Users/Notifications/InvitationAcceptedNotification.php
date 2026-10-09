<?php

declare(strict_types=1);

namespace Modules\Users\Notifications;

use App\Models\User;
use App\Modules\NotificationMessage;
use App\Notifications\AppNotification;

/**
 * Tells the inviter that their invitee now has an account.
 */
final class InvitationAcceptedNotification extends AppNotification
{
    public function __construct(
        public readonly string $userId,
        public readonly string $userName,
    ) {
        //
    }

    public function message(User $notifiable): NotificationMessage
    {
        return new NotificationMessage(
            title: __(':name accepted your invitation', ['name' => $this->userName]),
            url: route('admin.users.edit', $this->userId),
            action: __('View user'),
        );
    }
}
