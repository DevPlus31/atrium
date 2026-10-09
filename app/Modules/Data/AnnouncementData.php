<?php

declare(strict_types=1);

namespace App\Modules\Data;

use App\Enums\AnnouncementLevel;
use App\Settings\AnnouncementSettings;
use Spatie\LaravelData\Data;

/**
 * The announcement every page shows while it is active.
 */
final class AnnouncementData extends Data
{
    public function __construct(
        public string $id,
        public string $message,
        public AnnouncementLevel $level,
    ) {
        //
    }

    public static function fromSettings(AnnouncementSettings $settings): self
    {
        return new self(
            id: $settings->version(),
            message: (string) $settings->message,
            level: $settings->level,
        );
    }
}
