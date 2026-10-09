<?php

declare(strict_types=1);

namespace Modules\Api\Actions;

use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

final readonly class RevokeApiToken
{
    /**
     * Delete the token; requests carrying it fail from now on.
     */
    public function handle(User $user, PersonalAccessToken $token): void
    {
        $token->delete();

        activity('users')
            ->performedOn($user)
            ->event('api-token-revoked')
            ->withProperties(['attributes' => ['name' => $token->name]])
            ->log('api-token-revoked');
    }
}
