<?php

declare(strict_types=1);

use Modules\Shop\Domain\Exceptions\InvalidOrderNumber;
use Modules\Shop\Domain\ValueObjects\OrderNumber;

it('generates numbers in the order number format', function (): void {
    $number = OrderNumber::generate();

    expect((string) $number)->toMatch(OrderNumber::PATTERN)
        ->toStartWith('ORD-'.now()->format('ymd').'-');
});

it('normalises and compares numbers by value', function (): void {
    $number = new OrderNumber(' ord-261007-ab12cd ');

    expect((string) $number)->toBe('ORD-261007-AB12CD')
        ->and($number->equals(new OrderNumber('ORD-261007-AB12CD')))->toBeTrue()
        ->and($number->equals(new OrderNumber('ORD-261007-ZZ99ZZ')))->toBeFalse();
});

it('rejects malformed numbers', function (string $value): void {
    expect(fn (): OrderNumber => new OrderNumber($value))->toThrow(InvalidOrderNumber::class);
})->with(['', 'ORD-1', 'INV-261007-AB12CD', 'ORD-261007-AB12C!']);
