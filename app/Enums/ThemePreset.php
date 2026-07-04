<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Named preset themes. Each non-default preset is a pure token-value block
 * in resources/css/themes/<name>.css scoped to [data-theme='<name>'];
 * registering a case here is the server half of adding a preset.
 */
enum ThemePreset: string
{
    case Default = 'default';
    case Ember = 'ember';
    case Contrast = 'contrast';
}
