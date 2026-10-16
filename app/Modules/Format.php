<?php

declare(strict_types=1);

namespace App\Modules;

use App\Settings\BusinessSettings;
use Illuminate\Support\Number;
use NumberFormatter;
use RuntimeException;

/**
 * Figures in the business's regional format (Settings → Business), for what
 * the server writes itself: emails, notifications, exports. Pages use the
 * same rules through useFormatters().
 */
final readonly class Format
{
    /**
     * An amount in minor units (cents) as money, e.g. "$1,234.50": in the
     * business currency unless another is given. Currencies without
     * decimals (JPY) are handled.
     */
    public static function money(int $minorUnits, ?string $currency = null): string
    {
        $settings = self::settings();
        $currency ??= $settings->currency;
        $digits = new NumberFormatter($settings->locale.'@currency='.$currency, NumberFormatter::CURRENCY)
            ->getAttribute(NumberFormatter::FRACTION_DIGITS);

        return self::orFail(Number::currency($minorUnits / 10 ** $digits, $currency, self::locale()));
    }

    /**
     * A ratio as a percentage: 0.125 → "12.5%", with at most the given
     * number of decimals.
     */
    public static function percent(float $ratio, int $maxDecimals = 1): string
    {
        return self::orFail(Number::percentage($ratio * 100, maxPrecision: $maxDecimals, locale: self::locale()));
    }

    /**
     * A distance given in kilometres, in the business distance unit:
     * 12.3 → "12.3 km" or "7.6 mi".
     */
    public static function distance(float $kilometers, int $maxDecimals = 1): string
    {
        $unit = self::settings()->distance_unit;
        $number = self::orFail(Number::format($unit->fromKilometers($kilometers), maxPrecision: $maxDecimals, locale: self::locale()));

        return $number.' '.$unit->value;
    }

    private static function locale(): string
    {
        return self::settings()->locale;
    }

    private static function settings(): BusinessSettings
    {
        return resolve(BusinessSettings::class);
    }

    private static function orFail(string|false $formatted): string
    {
        return $formatted === false ? throw new RuntimeException('The number could not be formatted.') : $formatted;
    }
}
