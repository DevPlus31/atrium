<?php

declare(strict_types=1);

use Modules\Shop\Actions\UpdateShopSettings;
use Modules\Shop\Settings\ShopSettings;
use Spatie\Activitylog\Models\Activity;

it('saves the default currency and records the change', function (): void {
    $settings = resolve(ShopSettings::class);
    $old = $settings->default_currency;

    resolve(UpdateShopSettings::class)->handle($settings, 'EUR');

    expect(resolve(ShopSettings::class)->refresh()->default_currency)->toBe('EUR')
        ->and(Activity::query()->where('description', 'settings-updated')->sole()->properties->all())->toBe([
            'old' => ['default_currency' => $old],
            'attributes' => ['default_currency' => 'EUR'],
        ]);
});
