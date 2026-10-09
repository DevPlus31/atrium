<?php

declare(strict_types=1);

namespace App\Modules\Data;

use App\Models\User;
use Spatie\LaravelData\Data;

/**
 * The signed-in user as shared with every page: identity only — roles and
 * permissions stay on the server, which ships `can` maps where needed.
 */
final class AuthUserData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public string $email,
        public ?string $email_verified_at,
        public ?string $avatar,
        public string $created_at,
        public string $updated_at,
    ) {
        //
    }

    public static function fromModel(User $user): self
    {
        return new self(
            id: $user->id,
            name: $user->name,
            email: $user->email,
            email_verified_at: $user->email_verified_at?->toIso8601String(),
            avatar: $user->avatarUrl(),
            created_at: $user->created_at->toIso8601String(),
            updated_at: $user->updated_at->toIso8601String(),
        );
    }
}
