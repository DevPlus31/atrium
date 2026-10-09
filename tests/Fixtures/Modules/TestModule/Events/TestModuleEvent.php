<?php

declare(strict_types=1);

namespace Tests\Fixtures\Modules\TestModule\Events;

final readonly class TestModuleEvent
{
    public function __construct(public string $payload)
    {
        //
    }
}
