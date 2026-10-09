<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use App\Modules\AuditLog;
use Lab404\Impersonate\Services\ImpersonateManager;

final readonly class LeaveImpersonation
{
    public function __construct(private ImpersonateManager $manager)
    {
        //
    }

    /**
     * Restore the impersonator's session and record the real admin as the
     * causer, with the impersonated user as the subject.
     */
    public function handle(User $impersonated): void
    {
        $impersonator = $this->manager->getImpersonator();

        $impersonated->leaveImpersonation();

        AuditLog::record(
            log: 'users',
            event: 'impersonation-left',
            subject: $impersonated,
            causer: $impersonator instanceof User ? $impersonator : null,
        );
    }
}
