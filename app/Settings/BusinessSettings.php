<?php

declare(strict_types=1);

namespace App\Settings;

use App\Enums\DistanceUnit;
use Spatie\LaravelSettings\Settings;

/**
 * How the business works, set by an administrator in Settings → Business:
 * its timezone (business days, opening hours, reports), the regional format
 * of its figures, its currency and its distance unit. Each user still reads
 * dates in their own language and timezone; amounts, percentages and
 * distances read the same for everyone (App\Modules\Format, useFormatters()).
 */
final class BusinessSettings extends Settings
{
    /**
     * An IANA timezone, e.g. "Europe/Paris".
     */
    public string $timezone;

    /**
     * A regional format (BCP 47), e.g. "en-US" or "fr-FR": decimal and
     * thousands separators, where the currency symbol goes.
     */
    public string $locale;

    /**
     * An ISO 4217 code, e.g. "USD": amounts without a currency of their own.
     */
    public string $currency;

    public DistanceUnit $distance_unit;

    public static function group(): string
    {
        return 'business';
    }
}
