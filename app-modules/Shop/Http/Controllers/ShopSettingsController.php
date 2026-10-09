<?php

declare(strict_types=1);

namespace Modules\Shop\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Shop\Actions\UpdateShopSettings;
use Modules\Shop\Http\Requests\UpdateShopSettingsRequest;
use Modules\Shop\Settings\ShopSettings;

#[Authorize('shop.settings.update')]
final readonly class ShopSettingsController
{
    public function edit(ShopSettings $settings): Response
    {
        return Inertia::render('shop::settings', [
            'defaultCurrency' => $settings->default_currency,
        ]);
    }

    public function update(UpdateShopSettingsRequest $request, ShopSettings $settings, UpdateShopSettings $action): RedirectResponse
    {
        $action->handle($settings, $request->defaultCurrency());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Settings saved.')]);

        return to_route('admin.shop.settings.edit');
    }
}
