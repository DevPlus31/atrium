<?php

declare(strict_types=1);

namespace Modules\Shop\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * The Shop module's own settings group, edited under Settings → Shop: the
 * pattern for any module that needs admin-editable configuration.
 */
final class ShopSettings extends Settings
{
    /**
     * The currency new orders start in (ISO 4217).
     */
    public string $default_currency;

    public static function group(): string
    {
        return 'shop';
    }
}
