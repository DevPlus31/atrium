<?php

declare(strict_types=1);

namespace Tests\Fixtures\Notifications;

use App\Models\User;
use App\Modules\NotificationMessage;
use App\Notifications\AppNotification;

/**
 * A notification for tests of the shell's notification plumbing.
 */
final class GreetingNotification extends AppNotification
{
    public function __construct(
        private readonly ?string $url = null,
        private readonly ?string $body = 'Glad you are here.',
    ) {
        //
    }

    public function message(User $notifiable): NotificationMessage
    {
        return new NotificationMessage(
            title: __('Welcome, :name', ['name' => $notifiable->name]),
            body: $this->body,
            url: $this->url,
            action: $this->url === null ? null : 'Take a look',
        );
    }
}
