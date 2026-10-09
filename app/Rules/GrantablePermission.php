<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Fails when the actor adds a permission to a role that they do not hold
 * themselves. Permissions the role already has are exempt.
 */
final readonly class GrantablePermission implements ValidationRule
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

        if (! $this->actor->canGrantPermission($value)) {
            $fail(__('You cannot grant the :permission permission.', ['permission' => $value]));
        }
    }
}
