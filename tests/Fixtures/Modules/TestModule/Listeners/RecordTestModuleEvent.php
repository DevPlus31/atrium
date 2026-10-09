<?php

declare(strict_types=1);

namespace Tests\Fixtures\Modules\TestModule\Listeners;

use Tests\Fixtures\Modules\TestModule\Events\TestModuleEvent;

final class RecordTestModuleEvent
{
    /**
     * @var list<string>
     */
    public static array $received = [];

    public function handle(TestModuleEvent $event): void
    {
        self::$received[] = $event->payload;
    }
}
