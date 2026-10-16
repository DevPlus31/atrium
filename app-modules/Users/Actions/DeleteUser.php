<?php

declare(strict_types=1);

namespace Modules\Users\Actions;

use App\Actions\ErasePersonalData;
use App\Models\User;
use App\Modules\AuditLog;
use Illuminate\Support\Facades\DB;
use Modules\Users\Domain\Repositories\UserRepository;

final readonly class DeleteUser
{
    public function __construct(
        private UserRepository $users,
        private ErasePersonalData $erase,
    ) {
        //
    }

    /**
     * Delete the account with the personal data it leaves behind.
     */
    public function __invoke(User $user): void
    {
        DB::transaction(function () use ($user): void {
            ($this->erase)($user);

            $this->users->delete($user);

            AuditLog::record(
                log: 'users',
                event: 'deleted',
                subject: $user,
                properties: [
                    'attributes' => ['name' => $user->name, 'email' => $user->email],
                ],
            );
        });
    }
}
