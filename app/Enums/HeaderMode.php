<?php

declare(strict_types=1);

namespace App\Enums;

enum HeaderMode: string
{
    case Sticky = 'sticky';
    case Static = 'static';
}
