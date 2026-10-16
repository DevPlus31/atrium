<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\PersonalDataRegistry;
use Modules\Shop\Infrastructure\Models\Order;
use Modules\Shop\PersonalData\OrdersPersonalData;

it('exports the orders placed with the user’s address, oldest first', function (): void {
    $user = User::factory()->create(['email' => 'jane@example.com']);
    Order::factory()->create(['customer_email' => 'someone@example.com']);
    $older = Order::factory()->create(['customer_email' => 'jane@example.com', 'created_at' => now()->subDay()]);
    $newer = Order::factory()->create(['customer_email' => 'jane@example.com']);

    $orders = resolve(OrdersPersonalData::class)($user);

    expect(array_column($orders, 'number'))->toBe([$older->number, $newer->number])
        ->and($orders[0])->toBe([
            'number' => $older->number,
            'status' => $older->status->value,
            'total_cents' => $older->total_cents,
            'currency' => $older->currency,
            'paid_at' => $older->paid_at?->toIso8601String(),
            'shipped_at' => $older->shipped_at?->toIso8601String(),
            'cancelled_at' => $older->cancelled_at?->toIso8601String(),
            'created_at' => $older->created_at->toIso8601String(),
        ]);
});

it('adds the orders to every data export, and keeps them when the account goes', function (): void {
    $user = User::factory()->create(['email' => 'jane@example.com']);
    Order::factory()->create(['customer_email' => 'jane@example.com']);
    $registry = resolve(PersonalDataRegistry::class);

    expect($registry->export($user)['shop.orders'])->toHaveCount(1);

    $registry->erase($user);

    expect(Order::query()->count())->toBe(1);
});
