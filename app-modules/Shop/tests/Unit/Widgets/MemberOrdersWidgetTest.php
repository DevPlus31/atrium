<?php

declare(strict_types=1);

use App\Models\User;
use Modules\Shop\Actions\CreateOrder;
use Modules\Shop\Domain\Enums\OrderStatus;
use Modules\Shop\Infrastructure\Models\Order;
use Modules\Shop\Widgets\MemberOrdersWidget;

it('lists the five latest orders placed with the member email', function (): void {
    $user = User::factory()->create(['email' => 'jane@example.com']);
    Order::factory()->create(['customer_email' => 'someone@example.com']);

    foreach (range(1, 6) as $day) {
        Order::factory()->create([
            'customer_email' => 'jane@example.com',
            'created_at' => now()->subDays(7 - $day),
        ]);
    }

    $latest = Order::factory()->paid()->create([
        'customer_email' => 'jane@example.com',
        'total_cents' => 1250,
        'currency' => 'EUR',
    ]);

    $data = new MemberOrdersWidget()($user);

    expect($data->total)->toBe(7)
        ->and($data->orders)->toHaveCount(5)
        ->and($data->orders[0])->toBe([
            'id' => $latest->id,
            'number' => $latest->number,
            'status' => OrderStatus::Paid,
            'total_cents' => 1250,
            'currency' => 'EUR',
            'placed_at' => now()->toIso8601String(),
        ]);
});

it('is empty for a member without orders', function (): void {
    $data = new MemberOrdersWidget()(User::factory()->create());

    expect($data->total)->toBe(0)
        ->and($data->orders)->toBe([]);
});

it('finds orders placed with differently cased email addresses', function (): void {
    $user = User::factory()->create(['email' => 'jane@example.com']);
    resolve(CreateOrder::class)->handle(' Jane@Example.COM ', 100, 'USD');

    expect(new MemberOrdersWidget()($user)->total)->toBe(1)
        ->and(Order::query()->sole()->customer_email)->toBe('jane@example.com');
});
