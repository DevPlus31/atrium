<?php

declare(strict_types=1);

namespace Modules\Settings\Http\Controllers;

use App\Modules\Toast;
use App\Settings\AnnouncementSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Settings\Actions\RemoveAnnouncement;
use Modules\Settings\Actions\UpdateAnnouncement;
use Modules\Settings\Data\AnnouncementSettingsData;
use Modules\Settings\Http\Requests\UpdateAnnouncementRequest;

#[Authorize('settings.update')]
final readonly class AnnouncementController
{
    public function edit(AnnouncementSettings $settings): Response
    {
        return Inertia::render('settings::announcement', [
            'settings' => AnnouncementSettingsData::fromSettings($settings),
            'maxLength' => UpdateAnnouncementRequest::MAX_LENGTH,
        ]);
    }

    public function update(UpdateAnnouncementRequest $request, AnnouncementSettings $settings, UpdateAnnouncement $action): RedirectResponse
    {
        $action->handle($settings, $request->message(), $request->level(), $request->endsAt());

        Toast::success(__('Announcement published.'));

        return to_route('admin.settings.announcement.edit');
    }

    public function destroy(AnnouncementSettings $settings, RemoveAnnouncement $action): RedirectResponse
    {
        $action->handle($settings);

        Toast::success(__('Announcement removed.'));

        return to_route('admin.settings.announcement.edit');
    }
}
