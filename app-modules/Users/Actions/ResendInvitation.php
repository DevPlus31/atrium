<?php

declare(strict_types=1);

namespace Modules\Users\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Users\Domain\Repositories\InvitationRepository;
use Modules\Users\Infrastructure\Models\Invitation;

final readonly class ResendInvitation
{
    public function __construct(private InvitationRepository $invitations)
    {
        //
    }

    /**
     * Email the invitation again with a fresh expiry.
     */
    public function handle(Invitation $invitation): void
    {
        DB::transaction(function () use ($invitation): void {
            $invitation->renew();

            $this->invitations->save($invitation);

            activity('users')
                ->performedOn($invitation)
                ->event('invitation-resent')
                ->withProperties(['attributes' => ['email' => $invitation->email]])
                ->log('invitation-resent');
        });

        $invitation->flushDomainEvents();
    }
}
