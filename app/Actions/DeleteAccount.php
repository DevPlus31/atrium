<?php

declare(strict_types=1);

namespace App\Actions;

use App\Domain\Exceptions\LastAdministrator;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class DeleteAccount
{
    /**
     * Delete the user's own account, unless they are the last one able to
     * run the app (the only admin or super-admin).
     */
    public function handle(User $user): void
    {
        $role = $user->soleAdministrativeRole();

        if ($role !== null) {
            throw LastAdministrator::forRole($role);
        }

        DB::transaction(function () use ($user): void {
            $user->delete();

            activity('users')
                ->performedOn($user)
                ->causedBy($user)
                ->event('account-deleted')
                ->withProperties(['attributes' => ['name' => $user->name, 'email' => $user->email]])
                ->log('account-deleted');
        });
    }
}
