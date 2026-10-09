<?php

declare(strict_types=1);

namespace Modules\Users\Actions;

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
            $this->invitations->delete($invitation);

            activity('users')
                ->performedOn($invitation)
                ->event('invitation-revoked')
                ->withProperties(['attributes' => ['email' => $invitation->email]])
                ->log('invitation-revoked');
        });
    }
}
