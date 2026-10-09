<?php

declare(strict_types=1);

use App\Models\User;
use Modules\Shop\Infrastructure\Models\Order;
use Modules\Shop\Policies\OrderPolicy;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    $this->artisan('admin:sync-permissions')->assertSuccessful();
});

it('lets admins view and create orders, but not members', function (): void {
    $policy = new OrderPolicy();

    expect($policy->viewAny(adminUser()))->toBeTrue()
        ->and($policy->create(adminUser()))->toBeTrue()
        ->and($policy->viewAny(User::factory()->create()))->toBeFalse()
        ->and($policy->create(User::factory()->create()))->toBeFalse();
});

it('allows editing only pending orders', function (): void {
    $policy = new OrderPolicy();
    $admin = adminUser();

    expect($policy->update($admin, Order::factory()->create()))->toBeTrue()
        ->and($policy->update($admin, Order::factory()->paid()->create()))->toBeFalse();
});

it('allows status changes only while the workflow has a move left', function (): void {
    $policy = new OrderPolicy();
    $admin = adminUser();

    expect($policy->transition($admin, Order::factory()->paid()->create()))->toBeTrue()
        ->and($policy->transition($admin, Order::factory()->shipped()->create()))->toBeFalse()
        ->and($policy->transition($admin, Order::factory()->cancelled()->create()))->toBeFalse();
});

it('allows deleting only pending or cancelled orders', function (): void {
    $policy = new OrderPolicy();
    $admin = adminUser();

    expect($policy->delete($admin, Order::factory()->cancelled()->create()))->toBeTrue()
        ->and($policy->delete($admin, Order::factory()->shipped()->create()))->toBeFalse();
});

it('needs the matching permission whatever the order state', function (): void {
    Role::findByName('admin')->revokePermissionTo(['orders.update', 'orders.delete']);
    $policy = new OrderPolicy();
    $admin = adminUser();
    $pending = Order::factory()->create();

    expect($policy->update($admin, $pending))->toBeFalse()
        ->and($policy->transition($admin, $pending))->toBeFalse()
        ->and($policy->delete($admin, $pending))->toBeFalse();
});
