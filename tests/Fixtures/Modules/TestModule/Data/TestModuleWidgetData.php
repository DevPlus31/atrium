<?php

declare(strict_types=1);

namespace Tests\Fixtures\Modules\TestModule\Data;

use Spatie\LaravelData\Data;

final class TestModuleWidgetData extends Data
{
    public function __construct(
        public int $count,
    ) {
        //
    }
}
