<?php

declare(strict_types=1);

namespace Modules\Settings\Http\Controllers;

use App\Modules\Data\BusinessData;
use App\Modules\Toast;
use App\Settings\BusinessSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Settings\Actions\UpdateBusinessSettings;
use Modules\Settings\Http\Requests\UpdateBusinessSettingsRequest;

#[Authorize('settings.update')]
final readonly class BusinessSettingsController
{
    public function edit(BusinessSettings $settings): Response
    {
        return Inertia::render('settings::business', [
            'settings' => BusinessData::fromSettings($settings),
        ]);
    }

    public function update(UpdateBusinessSettingsRequest $request, BusinessSettings $settings, UpdateBusinessSettings $action): RedirectResponse
    {
        $action($settings, $request->timezone(), $request->locale(), $request->currency(), $request->distanceUnit());

        Toast::success(__('Settings saved.'));

        return to_route('admin.settings.business.edit');
    }
}
