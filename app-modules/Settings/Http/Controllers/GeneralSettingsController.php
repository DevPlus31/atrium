<?php

declare(strict_types=1);

namespace Modules\Settings\Http\Controllers;

use App\Modules\Toast;
use App\Settings\GeneralSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Settings\Actions\UpdateGeneralSettings;
use Modules\Settings\Data\GeneralSettingsData;
use Modules\Settings\Http\Requests\UpdateGeneralSettingsRequest;

#[Authorize('settings.update')]
final readonly class GeneralSettingsController
{
    public function edit(GeneralSettings $settings): Response
    {
        return Inertia::render('settings::general', [
            'settings' => GeneralSettingsData::fromSettings($settings),
        ]);
    }

    public function update(UpdateGeneralSettingsRequest $request, GeneralSettings $settings, UpdateGeneralSettings $action): RedirectResponse
    {
        $action->handle($settings, $request->supportEmail(), $request->registrationOpen());

        Toast::success(__('Settings saved.'));

        return to_route('admin.settings.general.edit');
    }
}
