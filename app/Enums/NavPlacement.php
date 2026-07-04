<?php

declare(strict_types=1);

namespace App\Enums;

enum NavPlacement: string
{
    case SidebarLeft = 'sidebar-left';
    case SidebarRight = 'sidebar-right';
    case Topbar = 'topbar';
}
