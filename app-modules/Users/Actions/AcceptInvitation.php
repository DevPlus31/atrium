<?php

declare(strict_types=1);

namespace Modules\Users\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Users\Domain\Exceptions\AccountAlreadyExists;
use Modules\Users\Domain\Repositories\InvitationRepository;
use Modules\Users\Infrastructure\Models\Invitation;
use SensitiveParameter;
use Spatie\Permission\Models\Role;

final readonly class AcceptInvitation
{
    public function __construct(
        private CreateUser $createUser,
        private InvitationRepository $invitations,
    ) {
        //
    }

    /**
     * Create the invitee's account, verified (the link proved the address),
     * with the invited roles the inviter may still grant. The invitation is locked and
     * checked again inside the transaction, so it is used at most once; the
     * inviter hears about it after commit (InvitationAccepted).
     */
    public function handle(Invitation $invitation, string $name, #[SensitiveParameter] string $password): User
    {
        [$invitation, $user] = DB::transaction(function () use ($invitation, $name, $password): array {
            $invitation = $this->invitations->lockForUpdate($invitation);
            $invitation->ensurePending();

            if (User::query()->where('email', $invitation->email)->exists()) {
                throw AccountAlreadyExists::forEmail($invitation->email);
            }

            $user = $this->createUser->handle($name, $invitation->email, $password, $this->grantableRoles($invitation), verified: true);

            $invitation->accept($user);

            $this->invitations->save($invitation);

            return [$invitation, $user];
        });

        $invitation->flushDomainEvents();

        return $user;
    }

    /**
     * The invited roles that still exist and that the inviter may still
     * grant today: rights lost since the invitation was sent are not passed
     * on, and an invitation whose inviter is gone grants no role.
     *
     * @return list<string>
     */
    private function grantableRoles(Invitation $invitation): array
    {
        $inviter = $invitation->inviter;

        if (! $inviter instanceof User || ! $inviter->can('create', User::class)) {
            return [];
        }

        $roles = [];

        foreach (Role::query()->whereIn('name', $invitation->roles)->with('permissions')->get() as $role) {
            if ($inviter->canGrantRole($role)) {
                $roles[] = $role->name;
            }
        }

        return $roles;
    }
}
