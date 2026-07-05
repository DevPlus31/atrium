<?php

declare(strict_types=1);

namespace Modules\Audit\Data;

use App\Models\User;
use Spatie\Activitylog\Models\Activity;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;

final class ActivityData extends Data
{
    /**
     * @param  array{name: string, email: string}|null  $causer
     * @param  array<string, mixed>  $changes
     */
    public function __construct(
        public string $id,
        public ?string $log_name,
        public ?string $event,
        public string $description,
        public ?array $causer,
        public ?string $subject_type,
        public ?string $subject_id,
        #[LiteralTypeScriptType('Record<string, unknown>')]
        public array $changes,
        public string $created_at,
    ) {
        //
    }

    public static function fromModel(Activity $activity): self
    {
        $causer = $activity->causer;

        /** @var array<string, mixed> $changes */
        $changes = $activity->properties?->toArray() ?? [];

        return new self(
            id: (string) $activity->id,
            log_name: $activity->log_name,
            event: $activity->event,
            description: $activity->description,
            causer: $causer instanceof User
                ? ['name' => $causer->name, 'email' => $causer->email]
                : null,
            subject_type: $activity->subject_type === null ? null : class_basename($activity->subject_type),
            subject_id: $activity->subject_id === null ? null : (string) $activity->subject_id,
            changes: $changes,
            created_at: (string) $activity->created_at?->toIso8601String(),
        );
    }
}
