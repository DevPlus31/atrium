<?php

declare(strict_types=1);

namespace Tests\Fixtures\Modules\TestModule\Widgets;

final readonly class NotDataWidget
{
    public function __invoke(): string
    {
        return 'not a data object';
    }
}
