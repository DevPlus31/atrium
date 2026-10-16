<?php

declare(strict_types=1);

namespace App\Enums;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use RuntimeException;

/**
 * The public legal pages: each is a Markdown file per language in
 * resources/legal/<locale>/<value>.md.
 */
enum LegalDocument: string
{
    case Terms = 'terms';
    case Privacy = 'privacy';
    case Cookies = 'cookies';

    public function title(): string
    {
        return match ($this) {
            self::Terms => __('Terms of Service'),
            self::Privacy => __('Privacy Policy'),
            self::Cookies => __('Cookie Policy'),
        };
    }

    /**
     * The document's Markdown file in the current language, or in the
     * fallback language when it has not been translated.
     */
    public function path(): string
    {
        foreach (array_unique([App::getLocale(), Config::string('app.fallback_locale')]) as $locale) {
            $path = resource_path("legal/{$locale}/{$this->value}.md");

            if (is_file($path)) {
                return $path;
            }
        }

        throw new RuntimeException(sprintf('The %s document has no file in resources/legal for the [%s] locale or the fallback locale.', $this->value, App::getLocale()));
    }
}
