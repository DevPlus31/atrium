<?php

declare(strict_types=1);

namespace App\Enums;

use Illuminate\Support\Facades\Config;

/**
 * The optional cookie and tracking purposes a visitor opts into. Only those
 * listed in config('privacy.consent.categories') are offered. Strictly
 * necessary cookies need no consent and have no case here.
 */
enum ConsentCategory: string
{
    case Analytics = 'analytics';
    case Marketing = 'marketing';

    /**
     * The categories this app offers, in the order the config lists them.
     *
     * @return list<self>
     */
    public static function offered(): array
    {
        return array_values(array_filter(array_map(
            self::tryFrom(...),
            array_filter(Config::array('privacy.consent.categories'), is_string(...)),
        )));
    }

    public function label(): string
    {
        return match ($this) {
            self::Analytics => __('Analytics'),
            self::Marketing => __('Marketing'),
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Analytics => __('Count visits and see how the app is used, so we can improve it.'),
            self::Marketing => __('Personalised advertising and sharing data with advertising partners.'),
        };
    }

    /**
     * Whether this purpose "sells" or "shares" personal information as the
     * CCPA defines it (cross-context behavioural advertising). A Global
     * Privacy Control signal turns these off, whatever the visitor chose.
     */
    public function isSaleOrSharing(): bool
    {
        return $this === self::Marketing;
    }
}
