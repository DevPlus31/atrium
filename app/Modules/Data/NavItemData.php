<?php

declare(strict_types=1);

namespace App\Modules\Data;

use Spatie\LaravelData\Data;

final class NavItemData extends Data
{
    public function __construct(
        public string $label,
        public string $routeName,
        public string $href,
        public ?string $icon,
        public ?string $group,
        public int $sort,
    ) {
        //
    }
}
