<?php

declare(strict_types=1);

namespace Modules\Settings\Http\Controllers;

use App\Modules\Toast;
use App\Settings\GeneralSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Modules\Settings\Actions\RemoveLogo;
use Modules\Settings\Actions\UpdateLogo;
use Modules\Settings\Http\Requests\UpdateLogoRequest;

#[Authorize('settings.update')]
final readonly class LogoController
{
    public function update(UpdateLogoRequest $request, GeneralSettings $settings, UpdateLogo $action): RedirectResponse
    {
        $action->handle($settings, $request->logo());

        Toast::success(__('Logo updated.'));

        return to_route('admin.settings.general.edit');
    }

    public function destroy(GeneralSettings $settings, RemoveLogo $action): RedirectResponse
    {
        $action->handle($settings);

        Toast::success(__('Logo removed.'));

        return to_route('admin.settings.general.edit');
    }
}
