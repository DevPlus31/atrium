<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use App\Modules\AuditLog;
use App\Modules\PersonalDataRegistry;
use Illuminate\Support\Facades\DB;

final readonly class ErasePersonalData
{
    public function __construct(private PersonalDataRegistry $modules)
    {
        //
    }

    /**
     * Erase what an account leaves behind outside its own row, just before
     * that row is deleted (GDPR Art. 17, the CCPA "right to delete"): each
     * module's records, API tokens, notifications and browser sessions.
     * Passkeys, roles and the profile photo go with the row itself; consent
     * records stay as anonymous proof (deleting the user clears their link).
     * Call it inside the transaction that deletes the user.
     */
    public function __invoke(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $this->modules->erase($user);

            $user->tokens()->delete();
            $user->notifications()->delete();
            $user->browserSessions()->delete();

            AuditLog::record(log: 'privacy', event: 'personal-data-erased', subject: $user);
        });
    }
}
