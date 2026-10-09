<?php

declare(strict_types=1);

namespace Modules\Users\Data;

use Modules\Users\Infrastructure\Models\Invitation;
use Spatie\LaravelData\Data;

final class InvitationData extends Data
{
    /**
     * @param  list<string>  $roles
     */
    public function __construct(
        public string $id,
        public string $email,
        public array $roles,
        public ?string $invited_by,
        public string $expires_at,
    ) {
        //
    }

    public static function fromModel(Invitation $invitation): self
    {
        return new self(
            id: $invitation->id,
            email: $invitation->email,
            roles: $invitation->roles,
            invited_by: $invitation->inviter?->name,
            expires_at: $invitation->expires_at->toIso8601String(),
        );
    }
}
