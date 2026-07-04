<?php

declare(strict_types=1);

namespace App\Modules\Data;

use App\Enums\ContentWidth;
use App\Enums\Direction;
use App\Enums\HeaderMode;
use App\Enums\NavPlacement;
use App\Enums\SidebarCollapsible;
use App\Enums\SidebarVariant;
use Spatie\LaravelData\Data;

/**
 * The layout contract (docs/specs/theming.md): an enumerated set of
 * structural options interpreted only by AdminLayout. Property names are
 * the wire format shared via Inertia, cookies, and the users table.
 */
final class LayoutConfigData extends Data
{
    public function __construct(
        public NavPlacement $nav_placement,
        public SidebarVariant $sidebar_variant,
        public SidebarCollapsible $sidebar_collapsible,
        public ContentWidth $content_width,
        public HeaderMode $header,
        public Direction $direction,
    ) {
        //
    }
}
