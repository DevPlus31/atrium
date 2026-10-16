<?php

declare(strict_types=1);

use App\Modules\BusinessTime;
use App\Settings\BusinessSettings;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;

beforeEach(function (): void {
    $settings = resolve(BusinessSettings::class);
    $settings->timezone = 'Europe/Paris';
    $settings->save();

    $this->travelTo(CarbonImmutable::parse('2026-10-16 22:30:00', 'UTC'));
});

it('reads the business timezone and clock', function (): void {
    expect(BusinessTime::timezone())->toBe('Europe/Paris')
        ->and(BusinessTime::now()->tzName)->toBe('Europe/Paris')
        ->and(BusinessTime::now()->format('Y-m-d H:i'))->toBe('2026-10-17 00:30');
});

it('turns a business wall-clock time into the UTC instant to store', function (): void {
    expect(BusinessTime::toUtc('2026-10-16 09:00')->toIso8601String())->toBe('2026-10-16T07:00:00+00:00')
        ->and(BusinessTime::toUtc('2026-12-16 09:00')->toIso8601String())->toBe('2026-12-16T08:00:00+00:00')
        ->and(BusinessTime::toUtc('2026-10-16T09:00:00-04:00')->toIso8601String())->toBe('2026-10-16T13:00:00+00:00')
        ->and(BusinessTime::toUtc(Date::parse('2026-10-16 09:00', 'Asia/Tokyo'))->toIso8601String())->toBe('2026-10-16T00:00:00+00:00');
});

it('shows a stored instant on the business clock', function (): void {
    expect(BusinessTime::fromUtc('2026-10-16 07:00:00')->toIso8601String())->toBe('2026-10-16T09:00:00+02:00')
        ->and(BusinessTime::fromUtc(CarbonImmutable::parse('2026-10-16 07:00:00', 'UTC'))->format('H:i'))->toBe('09:00');
});

it('gives the UTC bounds of a business day', function (): void {
    [$start, $end] = BusinessTime::dayInUtc();

    expect($start->toIso8601String())->toBe('2026-10-16T22:00:00+00:00')
        ->and($end->format('Y-m-d H:i:s'))->toBe('2026-10-17 21:59:59');

    [$start] = BusinessTime::dayInUtc('2026-12-25');

    expect($start->toIso8601String())->toBe('2026-12-24T23:00:00+00:00');

    [$start] = BusinessTime::dayInUtc(CarbonImmutable::parse('2026-10-16 23:30:00', 'UTC'));

    expect($start->toIso8601String())->toBe('2026-10-16T22:00:00+00:00');
});
