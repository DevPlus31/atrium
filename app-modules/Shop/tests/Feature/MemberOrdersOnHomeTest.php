<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Collection;
use Inertia\Testing\AssertableInertia;
use Laravel\Pennant\Feature;
use Modules\Shop\Infrastructure\Models\Order;

beforeEach(function (): void {
    $this->artisan('admin:sync-permissions')->assertSuccessful();
});

it('adds its widget to the member home and resolves it for the viewer', function (): void {
    $user = User::factory()->create(['email' => 'jane@example.com']);
    Order::factory()->create(['customer_email' => 'jane@example.com']);

    $this->actingAs($user)->get(route('member.dashboard'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->where('widgets', fn (Collection $widgets): bool => collect($widgets)->contains('key', 'shop.my-orders'))
            ->loadDeferredProps('widgets', fn (AssertableInertia $reloaded): AssertableInertia => $reloaded
                ->where('widget:shop_my-orders.total', 1)
                ->etc()));
});

it('leaves the member home when the module is off for the member', function (): void {
    $user = User::factory()->create();
    Feature::for($user)->deactivate('module:shop');

    $this->actingAs($user)->get(route('member.dashboard'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->where('widgets', fn (Collection $widgets): bool => collect($widgets)->doesntContain('key', 'shop.my-orders'))
            ->missing('widget:shop_my-orders'));
});
