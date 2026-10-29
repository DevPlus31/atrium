<?php

declare(strict_types=1);

namespace App\Enums;

enum Direction: string
{
    case Ltr = 'ltr';
    case Rtl = 'rtl';

    /**
     * The languages written right to left, by ISO 639-1 code.
     *
     * @var list<string>
     */
    private const array RTL_LANGUAGES = ['ar', 'dv', 'fa', 'he', 'ku', 'ps', 'ur', 'yi'];

    /**
     * The direction a locale (`ar`, `ar_EG`, `ar-MA`) reads in, from its
     * language subtag.
     */
    public static function forLocale(string $locale): self
    {
        $language = mb_strtolower(strtok($locale, '_-') ?: $locale);

        return in_array($language, self::RTL_LANGUAGES, true) ? self::Rtl : self::Ltr;
    }
}
