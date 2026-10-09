<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use SensitiveParameter;

final readonly class UpdateUserPassword
{
    /**
     * Change the password and revoke every API token: whoever knew the old
     * password may have minted one.
     */
    public function handle(User $user, #[SensitiveParameter] string $password): void
    {
        DB::transaction(function () use ($user, $password): void {
            $user->update([
                'password' => $password,
            ]);

            $user->tokens()->delete();
        });
    }
}
