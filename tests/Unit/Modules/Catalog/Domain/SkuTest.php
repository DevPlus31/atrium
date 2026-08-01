<?php

declare(strict_types=1);

use Modules\Catalog\Domain\Exceptions\InvalidSkuException;
use Modules\Catalog\Domain\ValueObjects\Sku;

it('normalizes a sku by trimming and uppercasing', function (): void {
    $sku = new Sku('  widget-01 ');

    expect($sku->value)->toBe('WIDGET-01')
        ->and((string) $sku)->toBe('WIDGET-01');
});

it('rejects an invalid sku', function (string $value): void {
    expect(fn (): Sku => new Sku($value))->toThrow(InvalidSkuException::class);
})->with(['', 'a', '-abc', 'has space', 'ünïcode']);

it('treats skus as equal when normalized values match', function (): void {
    $sku = new Sku('widget-01');

    expect($sku->equals(new Sku('WIDGET-01')))->toBeTrue()
        ->and($sku->equals(new Sku('widget-02')))->toBeFalse();
});
