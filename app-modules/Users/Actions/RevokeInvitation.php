<?php

declare(strict_types=1);

namespace Modules\Users\Actions;

use App\Modules\AuditLog;
use Illuminate\Support\Facades\DB;
use Modules\Users\Domain\Repositories\InvitationRepository;
use Modules\Users\Infrastructure\Models\Invitation;

final readonly class RevokeInvitation
{
    public function __construct(private InvitationRepository $invitations)
    {
        //
    }

    /**
     * Delete the invitation; its link stops working at once.
     */
    public function handle(Invitation $invitation): void
    {
        DB::transaction(function () use ($invitation): void {
            $invitation = $this->invitations->lockForUpdate($invitation);
            $invitation->revoke();

            $this->invitations->delete($invitation);

            AuditLog::record(
                log: 'users',
                event: 'invitation-revoked',
                subject: $invitation,
                properties: ['attributes' => ['email' => $invitation->email]],
            );
        });
    }
}
