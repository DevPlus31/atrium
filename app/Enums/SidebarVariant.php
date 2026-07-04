<?php

declare(strict_types=1);

namespace App\Enums;

enum SidebarVariant: string
{
    case Sidebar = 'sidebar';
    case Floating = 'floating';
    case Inset = 'inset';
}
