<?php

declare(strict_types=1);

use Modules\Shop\Domain\Enums\OrderStatus;

it('allows only the forward workflow transitions', function (OrderStatus $from, array $allowed): void {
    expect($from->transitions())->toBe($allowed);

    foreach (OrderStatus::cases() as $to) {
        expect($from->canTransitionTo($to))->toBe(in_array($to, $allowed, true));
    }
})->with([
    'pending' => [OrderStatus::Pending, [OrderStatus::Paid, OrderStatus::Cancelled]],
    'paid' => [OrderStatus::Paid, [OrderStatus::Shipped, OrderStatus::Cancelled]],
    'shipped' => [OrderStatus::Shipped, []],
    'cancelled' => [OrderStatus::Cancelled, []],
]);

it('names the timestamp each status records', function (OrderStatus $status, ?string $column): void {
    expect($status->timestampColumn())->toBe($column);
})->with([
    'pending' => [OrderStatus::Pending, null],
    'paid' => [OrderStatus::Paid, 'paid_at'],
    'shipped' => [OrderStatus::Shipped, 'shipped_at'],
    'cancelled' => [OrderStatus::Cancelled, 'cancelled_at'],
]);
