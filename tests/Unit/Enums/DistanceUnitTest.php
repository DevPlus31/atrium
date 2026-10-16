<?php

declare(strict_types=1);

use App\Enums\DistanceUnit;

it('labels every unit', function (DistanceUnit $unit): void {
    expect($unit->label())->not->toBe('');
})->with(DistanceUnit::cases());

it('converts kilometres', function (): void {
    expect(DistanceUnit::Kilometer->fromKilometers(10.0))->toBe(10.0)
        ->and(DistanceUnit::Mile->fromKilometers(1.609344))->toBe(1.0);
});
