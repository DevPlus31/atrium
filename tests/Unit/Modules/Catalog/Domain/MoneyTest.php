<?php

declare(strict_types=1);

use Modules\Catalog\Domain\Exceptions\InvalidMoneyException;
use Modules\Catalog\Domain\ValueObjects\Money;

it('accepts a non-negative amount and normalizes the currency', function (): void {
    $money = new Money(1500, 'usd');

    expect($money->amount)->toBe(1500)
        ->and($money->currency)->toBe('USD');
});

it('rejects a negative amount', function (): void {
    expect(fn (): Money => new Money(-1, 'USD'))->toThrow(InvalidMoneyException::class);
});

it('rejects an invalid currency', function (string $currency): void {
    expect(fn (): Money => new Money(100, $currency))->toThrow(InvalidMoneyException::class);
})->with(['US', 'USDD', '12A', '']);

it('treats money as equal when amount and currency match', function (): void {
    $money = new Money(1500, 'USD');

    expect($money->equals(new Money(1500, 'usd')))->toBeTrue()
        ->and($money->equals(new Money(1500, 'EUR')))->toBeFalse()
        ->and($money->equals(new Money(2000, 'USD')))->toBeFalse();
});
