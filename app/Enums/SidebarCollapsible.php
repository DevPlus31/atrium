<?php

declare(strict_types=1);

namespace App\Enums;

enum SidebarCollapsible: string
{
    case Offcanvas = 'offcanvas';
    case Icon = 'icon';
    case None = 'none';
}
