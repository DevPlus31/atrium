<?php

declare(strict_types=1);

namespace Modules\Catalog\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Catalog\Infrastructure\Models\Product;

/**
 * @extends Factory<Product>
 */
final class ProductFactory extends Factory
{
    /**
     * @var class-string<Product>
     */
    protected $model = Product::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'sku' => mb_strtoupper(fake()->unique()->bothify('???-####')),
            'price_cents' => fake()->numberBetween(100, 100_000),
            'currency' => 'USD',
            'description' => fake()->sentence(),
            'published_at' => null,
        ];
    }

    public function published(): self
    {
        return $this->state(fn (array $attributes): array => [
            'published_at' => now(),
        ]);
    }
}
