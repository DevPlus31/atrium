<?php

declare(strict_types=1);

namespace Modules\Settings\Actions;

use App\Enums\AnnouncementLevel;
use App\Settings\AnnouncementSettings;
use Carbon\CarbonInterface;

final readonly class UpdateAnnouncement
{
    /**
     * Publish (or change) the site-wide announcement. A change shows it
     * again to people who dismissed the previous one.
     */
    public function handle(AnnouncementSettings $settings, string $message, AnnouncementLevel $level, ?CarbonInterface $endsAt): void
    {
        $settings->message = $message;
        $settings->level = $level;
        $settings->ends_at = $endsAt?->toIso8601String();
        $settings->save();

        activity('settings')
            ->event('updated')
            ->withProperties(['attributes' => [
                'message' => $message,
                'level' => $level->value,
                'ends_at' => $settings->ends_at,
            ]])
            ->log('announcement-updated');
    }
}
