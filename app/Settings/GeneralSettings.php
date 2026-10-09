<?php

declare(strict_types=1);

namespace App\Settings;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Spatie\LaravelSettings\Settings;

/**
 * App-wide settings an administrator edits in the panel (Settings → General)
 * rather than in .env: the shell reads them for branding and sign-up.
 */
final class GeneralSettings extends Settings
{
    /**
     * The uploaded logo, relative to the media disk; null shows the built-in
     * mark.
     */
    public ?string $logo_path = null;

    /**
     * Where users can ask for help; shown on error pages when set.
     */
    public ?string $support_email = null;

    /**
     * Whether anyone may create an account at /register.
     */
    public bool $registration_open;

    public static function group(): string
    {
        return 'general';
    }

    /**
     * The uploaded logo's public URL, or null for the built-in mark.
     */
    public function logoUrl(): ?string
    {
        return $this->logo_path === null
            ? null
            : Storage::disk(Config::string('media-library.disk_name'))->url($this->logo_path);
    }
}
