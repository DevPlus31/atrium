<?php

declare(strict_types=1);

namespace Modules\Users\Actions;

use App\Models\User;

final readonly class ImpersonateUser
{
    /**
     * Switch the session to the given user and record who did it. Returns
     * false, recording nothing, when the impersonation is refused.
     */
    public function handle(User $impersonator, User $user): bool
    {
        if (! $impersonator->impersonate($user)) {
            return false;
        }

        activity('users')
            ->causedBy($impersonator)
            ->performedOn($user)
            ->event('impersonated')
            ->log('impersonated');

        return true;
    }
}
