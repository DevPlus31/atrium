<?php

declare(strict_types=1);

use Modules\Shop\Infrastructure\Models\Order;
use Modules\Shop\Queries\OrdersIndexQuery;
use Spatie\QueryBuilder\Exceptions\InvalidSortQuery;
use Spatie\QueryBuilder\QueryBuilder;

it('lists the newest orders first by default', function (): void {
    $older = Order::factory()->create(['created_at' => now()->subDay()]);
    $newer = Order::factory()->create();

    expect(indexQuery(OrdersIndexQuery::class)->builder()->pluck('id')->all())->toBe([$newer->id, $older->id]);
});

it('filters by search on the number or the customer email', function (): void {
    $ada = Order::factory()->create(['customer_email' => 'ada@example.com']);
    $grace = Order::factory()->create(['customer_email' => 'grace@example.com']);

    expect(indexQuery(OrdersIndexQuery::class, ['filter' => ['search' => 'ada@']])->builder()->pluck('id')->all())->toBe([$ada->id])
        ->and(indexQuery(OrdersIndexQuery::class, ['filter' => ['search' => $grace->number]])->builder()->pluck('id')->all())->toBe([$grace->id]);
});

it('filters by one or more statuses', function (): void {
    $pending = Order::factory()->create();
    $paid = Order::factory()->paid()->create();
    Order::factory()->shipped()->create();

    expect(indexQuery(OrdersIndexQuery::class, ['filter' => ['status' => 'paid']])->builder()->pluck('id')->all())->toBe([$paid->id])
        ->and(indexQuery(OrdersIndexQuery::class, ['filter' => ['status' => 'pending,paid']])->builder()->pluck('id')->sort()->values()->all())
        ->toBe(collect([$pending->id, $paid->id])->sort()->values()->all());
});

it('sorts by the allowed columns only', function (): void {
    Order::factory()->create(['total_cents' => 900]);
    Order::factory()->create(['total_cents' => 100]);

    expect(indexQuery(OrdersIndexQuery::class, ['sort' => 'total_cents'])->builder()->pluck('total_cents')->all())->toBe([100, 900])
        ->and(fn (): QueryBuilder => indexQuery(OrdersIndexQuery::class, ['sort' => 'id'])->builder())->toThrow(InvalidSortQuery::class);
});
