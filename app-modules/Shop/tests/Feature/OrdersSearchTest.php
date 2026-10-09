<?php

declare(strict_types=1);

use Modules\Shop\Infrastructure\Models\Order;

beforeEach(function (): void {
    $this->artisan('admin:sync-permissions')->assertSuccessful();
});

it('finds orders by number or customer email, newest first', function (): void {
    $older = Order::factory()->create(['customer_email' => 'ada@shop.example', 'created_at' => now()->subDay()]);
    $newer = Order::factory()->create(['customer_email' => 'ada@shop.example']);

    $this->actingAs(adminUser())
        ->getJson(route('search', ['q' => 'shop.example']))
        ->assertOk()
        ->assertJsonPath('groups.0.label', 'Orders')
        ->assertJsonPath('groups.0.results.0.title', $newer->number)
        ->assertJsonPath('groups.0.results.0.description', 'ada@shop.example')
        ->assertJsonPath('groups.0.results.1.title', $older->number);

    $this->actingAs(adminUser())
        ->getJson(route('search', ['q' => $older->number]))
        ->assertJsonPath('groups.0.results.0.title', $older->number);
});

it('opens pending orders for editing and others in the filtered list', function (): void {
    $pending = Order::factory()->create(['customer_email' => 'pending@shop.example']);
    $paid = Order::factory()->paid()->create(['customer_email' => 'paid@shop.example']);

    $this->actingAs(adminUser())
        ->getJson(route('search', ['q' => 'pending@shop']))
        ->assertJsonPath('groups.0.results.0.url', route('admin.orders.edit', $pending));

    $this->actingAs(adminUser())
        ->getJson(route('search', ['q' => 'paid@shop']))
        ->assertJsonPath('groups.0.results.0.url', route('admin.orders.index', ['filter' => ['search' => $paid->number]]));
});
