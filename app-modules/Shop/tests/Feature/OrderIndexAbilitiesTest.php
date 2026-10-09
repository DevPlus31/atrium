<?php

declare(strict_types=1);

use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    $this->withoutVite();

    $this->artisan('admin:sync-permissions')->assertSuccessful();
});

it('ships the create ability following the admin permission', function (): void {
    $admin = adminUser();

    $this->actingAs($admin)->get(route('admin.orders.index'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page->where('can.create', true));

    Role::findByName('admin')->revokePermissionTo('orders.create');

    $this->actingAs($admin->refresh())->get(route('admin.orders.index'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page->where('can.create', false));
});
