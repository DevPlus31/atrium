<?php

declare(strict_types=1);

use Modules\Shop\Infrastructure\Models\Order;

beforeEach(function (): void {
    $this->artisan('admin:sync-permissions')->assertSuccessful();
});

it('denies super-admins editing, deleting or moving finished orders', function (): void {
    $superAdmin = superAdminUser();
    $shipped = Order::factory()->shipped()->create();

    expect($superAdmin->can('update', $shipped))->toBeFalse()
        ->and($superAdmin->can('delete', $shipped))->toBeFalse()
        ->and($superAdmin->can('transition', $shipped))->toBeFalse();

    $this->actingAs($superAdmin)
        ->delete(route('admin.orders.destroy', $shipped))
        ->assertForbidden();

    $this->assertModelExists($shipped);
});
