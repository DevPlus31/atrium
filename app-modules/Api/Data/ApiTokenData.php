<?php

declare(strict_types=1);

namespace Modules\Api\Data;

use Laravel\Sanctum\PersonalAccessToken;
use Spatie\LaravelData\Data;

/**
 * A personal access token as the API tokens page lists it (never the
 * secret itself).
 */
final class ApiTokenData extends Data
{
    /**
     * @param  list<string>  $abilities
     */
    public function __construct(
        public int $id,
        public string $name,
        public array $abilities,
        public ?string $last_used_at,
        public ?string $expires_at,
        public string $created_at,
    ) {
        //
    }

    public static function fromModel(PersonalAccessToken $token): self
    {
        return new self(
            id: $token->id,
            name: $token->name,
            abilities: array_values(array_filter($token->abilities ?? [], is_string(...))),
            last_used_at: $token->last_used_at?->toIso8601String(),
            expires_at: $token->expires_at?->toIso8601String(),
            created_at: ($token->created_at ?? now())->toIso8601String(),
        );
    }
}
