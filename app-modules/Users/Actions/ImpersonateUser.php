<?php

declare(strict_types=1);

namespace Modules\Users\Actions;

use App\Models\User;
use App\Modules\AuditLog;

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

        AuditLog::record(
            log: 'users',
            event: 'impersonated',
            subject: $user,
            causer: $impersonator,
        );

        return true;
    }
}
