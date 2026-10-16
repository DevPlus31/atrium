<?php

declare(strict_types=1);

use App\Enums\DistanceUnit;
use App\Modules\Format;
use App\Settings\BusinessSettings;

/**
 * @param  array<string, mixed>  $values
 */
function businessSettings(array $values): void
{
    $settings = resolve(BusinessSettings::class);
    $settings->fill($values)->save();
}

it('formats minor units as money in the business currency and regional format', function (): void {
    expect(Format::money(123450))->toBe('$1,234.50');

    businessSettings(['locale' => 'fr-FR', 'currency' => 'EUR']);

    expect(Format::money(123450))->toBe("1\u{202F}234,50\u{A0}€");
});

it('formats an amount in its own currency, minding currencies without decimals', function (): void {
    expect(Format::money(1234, 'JPY'))->toBe('¥1,234')
        ->and(Format::money(1999, 'EUR'))->toBe('€19.99');
});

it('formats a ratio as a percentage', function (): void {
    expect(Format::percent(0.125))->toBe('12.5%')
        ->and(Format::percent(0.12346, 2))->toBe('12.35%')
        ->and(Format::percent(1))->toBe('100%');

    businessSettings(['locale' => 'fr-FR']);

    expect(Format::percent(0.125))->toBe("12,5\u{A0}%");
});

it('formats kilometres in the business distance unit', function (): void {
    expect(Format::distance(12.34))->toBe('12.3 km');

    businessSettings(['distance_unit' => DistanceUnit::Mile, 'locale' => 'fr-FR']);

    expect(Format::distance(16.09344))->toBe('10 mi')
        ->and(Format::distance(42.195, 2))->toBe('26,22 mi');
});
