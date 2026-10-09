<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Settings\AnnouncementSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cookie;

final readonly class DismissAnnouncementController
{
    /**
     * Hide the current announcement for this browser. The cookie names the
     * version, so a new announcement shows again.
     */
    public function __invoke(AnnouncementSettings $settings): RedirectResponse
    {
        Cookie::queue(Cookie::forever(AnnouncementSettings::DISMISSED_COOKIE, $settings->version(), sameSite: 'lax'));

        return back();
    }
}
