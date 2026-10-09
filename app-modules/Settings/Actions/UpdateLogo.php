<?php

declare(strict_types=1);

namespace Modules\Settings\Actions;

use App\Modules\AuditLog;
use App\Settings\GeneralSettings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

final readonly class UpdateLogo
{
    /**
     * Store the new logo under a fresh name (so caches never serve the old
     * one), point the settings at it and delete the previous file.
     */
    public function handle(GeneralSettings $settings, UploadedFile $logo): void
    {
        $disk = Storage::disk(Config::string('media-library.disk_name'));
        $previous = $settings->logo_path;

        $settings->logo_path = (string) $disk->putFile('branding', $logo, 'public');
        $settings->save();

        if ($previous !== null) {
            $disk->delete($previous);
        }

        AuditLog::record(
            log: 'settings',
            event: 'updated',
            description: 'logo-updated',
        );
    }
}
