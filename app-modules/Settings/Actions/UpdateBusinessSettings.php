<?php

declare(strict_types=1);

namespace Modules\Settings\Actions;

use App\Enums\DistanceUnit;
use App\Modules\AuditLog;
use App\Settings\BusinessSettings;
use Illuminate\Support\Facades\DB;

final readonly class UpdateBusinessSettings
{
    public function __invoke(BusinessSettings $settings, string $timezone, string $locale, string $currency, DistanceUnit $distanceUnit): void
    {
        $old = [
            'timezone' => $settings->timezone,
            'locale' => $settings->locale,
            'currency' => $settings->currency,
            'distance_unit' => $settings->distance_unit->value,
        ];

        DB::transaction(static function () use ($settings, $timezone, $locale, $currency, $distanceUnit, $old): void {
            $settings->timezone = $timezone;
            $settings->locale = $locale;
            $settings->currency = $currency;
            $settings->distance_unit = $distanceUnit;
            $settings->save();

            AuditLog::record(
                log: 'settings',
                event: 'updated',
                properties: [
                    'old' => $old,
                    'attributes' => ['timezone' => $timezone, 'locale' => $locale, 'currency' => $currency, 'distance_unit' => $distanceUnit->value],
                ],
                description: 'business-updated',
            );
        });
    }
}
