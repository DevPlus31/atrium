<?php

declare(strict_types=1);

namespace App\Modules\Data;

use Laravel\Passkeys\Passkey;
use Spatie\LaravelData\Data;

/**
 * One registered passkey, as the passkeys settings page lists it. The
 * credential itself never leaves the server.
 */
final class PasskeyData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $authenticator,
        public ?string $last_used_at,
        public ?string $created_at,
    ) {
        //
    }

    public static function fromModel(Passkey $passkey): self
    {
        return new self(
            id: $passkey->id,
            name: $passkey->name,
            authenticator: $passkey->authenticator,
            last_used_at: $passkey->last_used_at?->toIso8601String(),
            created_at: $passkey->created_at?->toIso8601String(),
        );
    }
}
