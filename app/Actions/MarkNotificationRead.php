<?php

declare(strict_types=1);

namespace App\Actions;

use Illuminate\Notifications\DatabaseNotification;

final readonly class MarkNotificationRead
{
    public function __invoke(DatabaseNotification $notification): void
    {
        $notification->markAsRead();
    }
}
