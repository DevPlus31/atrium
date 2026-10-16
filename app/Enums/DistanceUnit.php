<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * How distances read across the app (Settings → Business). Values are stored
 * in kilometres and converted for display.
 */
enum DistanceUnit: string
{
    case Kilometer = 'km';
    case Mile = 'mi';

    private const float KILOMETERS_PER_MILE = 1.609344;

    public function label(): string
    {
        return match ($this) {
            self::Kilometer => __('Kilometres'),
            self::Mile => __('Miles'),
        };
    }

    /**
     * A distance in kilometres, in this unit.
     */
    public function fromKilometers(float $kilometers): float
    {
        return match ($this) {
            self::Kilometer => $kilometers,
            self::Mile => $kilometers / self::KILOMETERS_PER_MILE,
        };
    }
}
