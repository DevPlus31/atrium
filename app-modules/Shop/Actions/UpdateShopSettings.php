<?php

declare(strict_types=1);

namespace Modules\Shop\Actions;

use Modules\Shop\Settings\ShopSettings;

final readonly class UpdateShopSettings
{
    public function handle(ShopSettings $settings, string $defaultCurrency): void
    {
        $old = $settings->default_currency;

        $settings->default_currency = $defaultCurrency;
        $settings->save();

        activity('shop')
            ->event('updated')
            ->withProperties([
                'old' => ['default_currency' => $old],
                'attributes' => ['default_currency' => $defaultCurrency],
            ])
            ->log('settings-updated');
    }
}
