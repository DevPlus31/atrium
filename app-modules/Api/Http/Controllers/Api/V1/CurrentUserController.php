<?php

declare(strict_types=1);

namespace Modules\Api\Http\Controllers\Api\V1;

use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Modules\Api\Http\Resources\V1\UserResource;

final readonly class CurrentUserController
{
    /**
     * The account the token belongs to.
     */
    public function __invoke(#[CurrentUser] User $user): UserResource
    {
        return new UserResource($user);
    }
}
