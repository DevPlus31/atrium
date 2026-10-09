<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;

final readonly class MarkAllNotificationsRead
{
    /**
     * Mark every unread notification of the user read, in one query.
     */
    public function handle(User $user): void
    {
        $user->unreadNotifications()->update(['read_at' => now()]);
    }
}
