<?php

declare(strict_types=1);

namespace Modules\Users\Data;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Spatie\LaravelData\Data;

final class UserData extends Data
{
    /**
     * @param  list<string>  $roles
     * @param  array{update: bool, delete: bool}  $can
     */
    public function __construct(
        public string $id,
        public string $name,
        public string $email,
        public ?string $email_verified_at,
        public array $roles,
        public string $created_at,
        public array $can,
    ) {
        //
    }

    public static function fromModel(User $user): self
    {
        $viewer = Auth::user();

        /** @var list<string> $roles */
        $roles = $user->roles->pluck('name')->sort()->values()->all();

        return new self(
            id: $user->id,
            name: $user->name,
            email: $user->email,
            email_verified_at: $user->email_verified_at?->toIso8601String(),
            roles: $roles,
            created_at: $user->created_at->toIso8601String(),
            can: [
                'update' => $viewer?->can('update', $user) ?? false,
                'delete' => $viewer?->can('delete', $user) ?? false,
            ],
        );
    }
}
