<?php

declare(strict_types=1);

namespace Modules\Api\Actions;

use App\Models\User;
use App\Modules\AuditLog;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;

final readonly class RevokeApiToken
{
    /**
     * Delete the token; requests carrying it fail from now on.
     */
    public function handle(User $user, PersonalAccessToken $token): void
    {
        DB::transaction(function () use ($user, $token): void {
            $token->delete();

            AuditLog::record(
                log: 'users',
                event: 'api-token-revoked',
                subject: $user,
                properties: ['attributes' => ['name' => $token->name]],
            );
        });
    }
}
