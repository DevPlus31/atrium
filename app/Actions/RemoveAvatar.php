<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;

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

        activity('users')
            ->performedOn($user)
            ->event('avatar-removed')
            ->log('avatar-removed');
    }
}
