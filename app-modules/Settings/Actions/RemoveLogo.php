<?php

declare(strict_types=1);

namespace Modules\Settings\Actions;

use App\Settings\GeneralSettings;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

final readonly class RemoveLogo
{
    /**
     * Go back to the built-in mark and delete the uploaded file.
     */
    public function handle(GeneralSettings $settings): void
    {
        if ($settings->logo_path === null) {
            return;
        }

        Storage::disk(Config::string('media-library.disk_name'))->delete($settings->logo_path);

        $settings->logo_path = null;
        $settings->save();

        activity('settings')->event('updated')->log('logo-removed');
    }
}
