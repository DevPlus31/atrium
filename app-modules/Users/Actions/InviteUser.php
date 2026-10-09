<?php

declare(strict_types=1);

namespace Modules\Users\Actions;

use App\Domain\ValueObjects\Email;
use App\Models\User;
use App\Modules\AuditLog;
use Illuminate\Support\Facades\DB;
use Modules\Users\Domain\Exceptions\AccountAlreadyExists;
use Modules\Users\Domain\Exceptions\InvitationAlreadyPending;
use Modules\Users\Domain\Repositories\InvitationRepository;
use Modules\Users\Infrastructure\Models\Invitation;

final readonly class InviteUser
{
    public function __construct(private InvitationRepository $invitations)
    {
        //
    }

    /**
     * Invite someone to create an account with the given roles; the email
     * goes out once the invitation is saved (InvitationSent).
     *
     * @param  list<string>  $roles
     */
    public function handle(User $inviter, string $email, array $roles): Invitation
    {
        $email = new Email($email);

        $invitation = DB::transaction(function () use ($inviter, $email, $roles): Invitation {
            // One pending invitation per address, never for an existing account.
            if (User::query()->where('email', (string) $email)->exists()) {
                throw AccountAlreadyExists::forEmail((string) $email);
            }

            if (Invitation::pending()->where('email', (string) $email)->exists()) {
                throw InvitationAlreadyPending::forEmail((string) $email);
            }

            $invitation = Invitation::issue($email, $roles, $inviter);

            $this->invitations->save($invitation);

            AuditLog::record(
                log: 'users',
                event: 'invited',
                subject: $invitation,
                properties: ['attributes' => ['email' => $invitation->email, 'roles' => $roles]],
            );

            return $invitation;
        });

        $invitation->flushDomainEvents();

        return $invitation;
    }
}
