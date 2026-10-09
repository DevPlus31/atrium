<?php

declare(strict_types=1);

namespace Modules\Users\Domain\Repositories;

use App\Domain\Contracts\Repository;
use Modules\Users\Infrastructure\Models\Invitation;

interface InvitationRepository extends Repository
{
    /**
     * Re-read the invitation inside the current transaction, locking its row
     * so two simultaneous acceptances cannot both succeed.
     */
    public function lockForUpdate(Invitation $invitation): Invitation;

    public function save(Invitation $invitation): void;

    public function delete(Invitation $invitation): void;
}
