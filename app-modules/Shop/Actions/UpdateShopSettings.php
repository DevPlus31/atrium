<?php

declare(strict_types=1);

namespace Modules\Shop\Actions;

use App\Modules\AuditLog;
use Modules\Shop\Settings\ShopSettings;

final readonly class UpdateShopSettings
{
    public function handle(ShopSettings $settings, string $defaultCurrency): void
    {
        $old = $settings->default_currency;

        $settings->default_currency = $defaultCurrency;
        $settings->save();

        AuditLog::record(
            log: 'shop',
            event: 'updated',
            properties: [
                'old' => ['default_currency' => $old],
                'attributes' => ['default_currency' => $defaultCurrency],
            ],
            description: 'settings-updated',
        );
    }
}
