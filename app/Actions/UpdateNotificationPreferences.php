<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;

final readonly class UpdateNotificationPreferences
{
    /**
     * Turn notification emails on or off; the in-app bell always receives them.
     */
    public function handle(User $user, bool $notifyByEmail): void
    {
        $user->update(['notify_by_email' => $notifyByEmail]);
    }
}
