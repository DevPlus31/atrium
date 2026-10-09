<?php

declare(strict_types=1);

namespace App\Modules\Data;

use Illuminate\Notifications\DatabaseNotification;
use Spatie\LaravelData\Data;

/**
 * One stored notification as the bell and the notifications page show it.
 * The text was rendered in the user's language when it was sent.
 */
final class NotificationData extends Data
{
    public function __construct(
        public string $id,
        public string $title,
        public ?string $body,
        public ?string $url,
        public ?string $action,
        public ?string $read_at,
        public string $created_at,
    ) {
        //
    }

    public static function fromModel(DatabaseNotification $notification): self
    {
        $data = $notification->data;

        return new self(
            id: $notification->id,
            title: self::text($data, 'title') ?? '',
            body: self::text($data, 'body'),
            url: self::text($data, 'url'),
            action: self::text($data, 'action'),
            read_at: $notification->read_at?->toIso8601String(),
            created_at: ($notification->created_at ?? now())->toIso8601String(),
        );
    }

    /**
     * @param  array<array-key, mixed>  $data
     */
    private static function text(array $data, string $key): ?string
    {
        return is_string($data[$key] ?? null) ? $data[$key] : null;
    }
}
