<?php

declare(strict_types=1);

namespace Modules\Settings\Actions;

use App\Settings\AnnouncementSettings;

final readonly class RemoveAnnouncement
{
    public function handle(AnnouncementSettings $settings): void
    {
        if ($settings->message === null) {
            return;
        }

        $settings->message = null;
        $settings->ends_at = null;
        $settings->save();

        activity('settings')->event('updated')->log('announcement-removed');
    }
}
