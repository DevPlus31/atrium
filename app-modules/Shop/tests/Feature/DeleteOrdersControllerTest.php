<?php

declare(strict_types=1);

use Modules\Shop\Infrastructure\Models\Order;

beforeEach(function (): void {
    $this->artisan('admin:sync-permissions')->assertSuccessful();
});

it('deletes pending and cancelled orders and keeps the ones that must stay', function (): void {
    $pending = Order::factory()->create();
    $cancelled = Order::factory()->cancelled()->create();
    $paid = Order::factory()->paid()->create();

    $this->actingAs(adminUser())
        ->delete(route('admin.orders.bulk-destroy'), ['ids' => [$pending->id, $cancelled->id, $paid->id]])
        ->assertRedirectToRoute('admin.orders.index')
        ->assertToast('2 orders deleted. 1 could not be deleted.');

    expect(Order::query()->pluck('id')->all())->toBe([$paid->id]);
});

it('is forbidden when only kept orders are selected', function (): void {
    $paid = Order::factory()->paid()->create();

    $this->actingAs(adminUser())
        ->delete(route('admin.orders.bulk-destroy'), ['ids' => [$paid->id]])
        ->assertForbidden();
});

it('keeps guests and non-admins out', function (): void {
    assertAdminOnly('delete', route('admin.orders.bulk-destroy'), ['ids' => ['x']]);
});

it('validates the selection', function (mixed $ids): void {
    $this->actingAs(adminUser())
        ->delete(route('admin.orders.bulk-destroy'), ['ids' => $ids])
        ->assertSessionHasErrors('ids');
})->with(invalidBulkSelections());
