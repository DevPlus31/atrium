<?php

declare(strict_types=1);

namespace App\Actions;

use App\Domain\Exceptions\LastAdministrator;
use App\Models\User;
use App\Modules\AuditLog;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

final readonly class DeleteAccount
{
    /**
     * Delete the user's own account, unless they are the last one able to
     * run the app (the only admin or super-admin).
     */
    public function handle(User $user): void
    {
        DB::transaction(function () use ($user): void {
            // Lock the administrative roles first, so two last admins leaving
            // at the same moment cannot both see the other one still there.
            Role::query()
                ->whereIn('name', [User::PANEL_ROLE, User::SUPER_ADMIN_ROLE])
                ->lockForUpdate()
                ->get();

            $role = $user->soleAdministrativeRole();

            if ($role !== null) {
                throw LastAdministrator::forRole($role);
            }

            $user->delete();

            AuditLog::record(
                log: 'users',
                event: 'account-deleted',
                subject: $user,
                properties: ['attributes' => ['name' => $user->name, 'email' => $user->email]],
                causer: $user,
            );
        });
    }
}
