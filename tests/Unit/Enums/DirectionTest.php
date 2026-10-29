<?php

declare(strict_types=1);

use App\Enums\Direction;

it('reads right-to-left scripts right to left', function (string $locale): void {
    expect(Direction::forLocale($locale))->toBe(Direction::Rtl);
})->with(['ar', 'ar_EG', 'ar-MA', 'he', 'fa', 'ur']);

it('reads every other locale left to right', function (string $locale): void {
    expect(Direction::forLocale($locale))->toBe(Direction::Ltr);
})->with(['en', 'fr', 'es', 'es_MX', 'arn']);
