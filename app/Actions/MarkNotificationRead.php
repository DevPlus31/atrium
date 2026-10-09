<?php

declare(strict_types=1);

namespace App\Actions;

use Illuminate\Notifications\DatabaseNotification;

final readonly class MarkNotificationRead
{
    public function handle(DatabaseNotification $notification): void
    {
        $notification->markAsRead();
    }
}
