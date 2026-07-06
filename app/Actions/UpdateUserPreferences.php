<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;

final readonly class UpdateUserPreferences
{
    /**
     * Persist a validated subset of {appearance, theme, layout, locale}
     * preferences. Layout updates merge over the user's stored layout so
     * partial updates never drop previously chosen options.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function handle(User $user, array $attributes): void
    {
        $payload = [];

        if (array_key_exists('appearance', $attributes)) {
            $payload['appearance'] = $attributes['appearance'];
        }

        if (array_key_exists('theme', $attributes)) {
            $payload['theme'] = $attributes['theme'];
        }

        if (array_key_exists('locale', $attributes)) {
            $payload['locale'] = $attributes['locale'];
        }

        if (is_array($attributes['layout'] ?? null)) {
            $payload['layout'] = [
                ...$user->layout ?? [],
                ...$attributes['layout'],
            ];
        }

        if ($payload !== []) {
            $user->update($payload);
        }
    }
}
