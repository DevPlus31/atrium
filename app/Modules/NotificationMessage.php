<?php

declare(strict_types=1);

namespace App\Modules;

/**
 * What a notification says, once, for every channel: the bell and the
 * notifications page show it, the email is built from it.
 */
final readonly class NotificationMessage
{
    public function __construct(
        public string $title,
        public ?string $body = null,
        public ?string $url = null,
        public ?string $action = null,
    ) {
        //
    }
}
