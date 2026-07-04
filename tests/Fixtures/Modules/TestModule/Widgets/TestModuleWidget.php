<?php

declare(strict_types=1);

namespace Tests\Fixtures\Modules\TestModule\Widgets;

use Tests\Fixtures\Modules\TestModule\Data\TestModuleWidgetData;

final readonly class TestModuleWidget
{
    public function __invoke(): TestModuleWidgetData
    {
        return new TestModuleWidgetData(count: 3);
    }
}
