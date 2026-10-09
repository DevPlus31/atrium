<?php

declare(strict_types=1);

namespace Modules\Shop\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Shop\Domain\Enums\OrderStatus;
use Modules\Shop\Domain\ValueObjects\OrderNumber;
use Modules\Shop\Infrastructure\Models\Order;

/**
 * @extends Factory<Order>
 */
final class OrderFactory extends Factory
{
    /**
     * @var class-string<Order>
     */
    protected $model = Order::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'number' => (string) OrderNumber::generate(),
            'customer_email' => fake()->unique()->safeEmail(),
            'status' => OrderStatus::Pending,
            'total_cents' => fake()->numberBetween(500, 250_000),
            'currency' => 'USD',
        ];
    }

    public function paid(): self
    {
        return $this->state(fn (): array => [
            'status' => OrderStatus::Paid,
            'paid_at' => now(),
        ]);
    }

    public function shipped(): self
    {
        return $this->state(fn (): array => [
            'status' => OrderStatus::Shipped,
            'paid_at' => now(),
            'shipped_at' => now(),
        ]);
    }

    public function cancelled(): self
    {
        return $this->state(fn (): array => [
            'status' => OrderStatus::Cancelled,
            'cancelled_at' => now(),
        ]);
    }
}
