<?php

declare(strict_types=1);

namespace Modules\Dashboard\Data;

use Spatie\LaravelData\Data;

final class WidgetDescriptorData extends Data
{
    public function __construct(
        public string $key,
        public int $sort,
    ) {
        //
    }
}
