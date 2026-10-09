<?php

declare(strict_types=1);

namespace Modules\Api\Actions;

use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\NewAccessToken;

final readonly class CreateApiToken
{
    /**
     * Issue a personal access token carrying the given permissions (its
     * abilities). The plain-text token exists only in the returned object.
     *
     * @param  list<string>  $abilities
     */
    public function handle(User $user, string $name, array $abilities, CarbonInterface $expiresAt): NewAccessToken
    {
        return DB::transaction(function () use ($user, $name, $abilities, $expiresAt): NewAccessToken {
            $token = $user->createToken($name, $abilities, $expiresAt);

            activity('users')
                ->performedOn($user)
                ->event('api-token-created')
                ->withProperties(['attributes' => [
                    'name' => $name,
                    'abilities' => $abilities,
                    'expires_at' => $expiresAt->toIso8601String(),
                ]])
                ->log('api-token-created');

            return $token;
        });
    }
}
