<?php

declare(strict_types=1);

namespace Modules\Settings\Data;

use App\Enums\AnnouncementLevel;
use App\Settings\AnnouncementSettings;
use Spatie\LaravelData\Data;

final class AnnouncementSettingsData extends Data
{
    public function __construct(
        public ?string $message,
        public AnnouncementLevel $level,
        public ?string $ends_at,
        public bool $is_active,
    ) {
        //
    }

    public static function fromSettings(AnnouncementSettings $settings): self
    {
        return new self(
            message: $settings->message,
            level: $settings->level,
            ends_at: $settings->ends_at,
            is_active: $settings->isActive(),
        );
    }
}
