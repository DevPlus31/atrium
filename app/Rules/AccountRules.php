<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

/**
 * The validation rules for account fields, in one place: registration, the
 * profile, the admin user forms, invitations and the console all ask here,
 * so an address or password valid in one is valid in every other.
 */
final readonly class AccountRules
{
    /**
     * @return list<string>
     */
    public static function name(): array
    {
        return ['required', 'string', 'max:255'];
    }

    /**
     * A sign-in address no other account uses (the given one may keep it).
     *
     * @return list<mixed>
     */
    public static function email(?User $ignore = null): array
    {
        return ['required', ...ValidEmail::rules(), Rule::unique(User::class)->ignore($ignore?->id)];
    }

    /**
     * @return list<mixed>
     */
    public static function password(bool $confirmed = true): array
    {
        return $confirmed
            ? ['required', 'confirmed', Password::defaults()]
            : ['required', Password::defaults()];
    }

    /**
     * Roles to grant: existing ones the actor may grant (or that the account
     * already holds).
     *
     * @param  list<string>  $held
     * @return array{roles: list<mixed>, 'roles.*': list<mixed>}
     */
    public static function roles(User $actor, array $held = []): array
    {
        return [
            'roles' => ['array'],
            'roles.*' => ['string', Rule::exists(Role::class, 'name'), new GrantableRole($actor, $held)],
        ];
    }
}
