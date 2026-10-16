<?php

declare(strict_types=1);

namespace App\Modules\Data;

use App\Enums\DistanceUnit;
use App\Settings\BusinessSettings;
use Spatie\LaravelData\Data;

/**
 * The business settings every page needs to format figures (the `business`
 * shared prop, read by useFormatters()).
 */
final class BusinessData extends Data
{
    public function __construct(
        public string $timezone,
        public string $locale,
        public string $currency,
        public DistanceUnit $distance_unit,
    ) {
        //
    }

    public static function fromSettings(BusinessSettings $settings): self
    {
        return new self(
            timezone: $settings->timezone,
            locale: $settings->locale,
            currency: $settings->currency,
            distance_unit: $settings->distance_unit,
        );
    }
}
