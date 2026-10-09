<?php

declare(strict_types=1);

namespace Modules\Settings\Data;

use App\Settings\GeneralSettings;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Spatie\LaravelData\Data;

final class GeneralSettingsData extends Data
{
    public function __construct(
        public ?string $logo,
        public ?string $support_email,
        public bool $registration_open,
    ) {
        //
    }

    public static function fromSettings(GeneralSettings $settings): self
    {
        return new self(
            logo: $settings->logo_path === null
                ? null
                : Storage::disk(Config::string('media-library.disk_name'))->url($settings->logo_path),
            support_email: $settings->support_email,
            registration_open: $settings->registration_open,
        );
    }
}
