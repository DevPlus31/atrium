<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;
use Spatie\Permission\Models\Role;

/**
 * Fails when the actor assigns a role they may not grant. Roles the target
 * already holds are exempt, so editing an account never forces the actor to
 * strip roles they could not have granted themselves.
 */
final readonly class GrantableRole implements ValidationRule
{
    /**
     * @param  list<string>  $alreadyHeld
     */
    public function __construct(
        private User $actor,
        private array $alreadyHeld = [],
    ) {}

    /**
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || in_array($value, $this->alreadyHeld, true)) {
            return;
        }

        $role = Role::query()->with('permissions')->where('name', $value)->first();

        if ($role instanceof Role && ! $this->actor->canGrantRole($role)) {
            $fail(__('You cannot grant the :role role.', ['role' => $value]));
        }
    }
}
