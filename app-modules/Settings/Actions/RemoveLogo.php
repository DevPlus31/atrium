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

        $path = $settings->logo_path;

        // Settings first: if saving fails the logo still shows; a file left
        // behind by a failed delete is harmless, a missing one is not.
        $settings->logo_path = null;
        $settings->save();

        Storage::disk(Config::string('media-library.disk_name'))->delete($path);

        activity('settings')->event('updated')->log('logo-removed');
    }
}
