<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use App\Modules\AuditLog;

final readonly class RemoveAvatar
{
    /**
     * Delete the profile photo and its thumbnail, if there is one.
     */
    public function handle(User $user): void
    {
        if (! $user->hasMedia(User::AVATAR)) {
            return;
        }

        $user->clearMediaCollection(User::AVATAR);

        AuditLog::record(
            log: 'users',
            event: 'avatar-removed',
            subject: $user,
        );
    }
}
