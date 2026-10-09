<?php

declare(strict_types=1);

namespace Modules\Settings\Actions;

use App\Modules\AuditLog;
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

        AuditLog::record(
            log: 'settings',
            event: 'updated',
            description: 'announcement-removed',
        );
    }
}
