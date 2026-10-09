<?php

declare(strict_types=1);

beforeEach(function (): void {
    $this->artisan('admin:sync-permissions')->assertSuccessful();
});

it('keeps guests and non-admins out', function (): void {
    assertAdminOnly('get', route('horizon.index'));
});

it('forbids admins without the system.horizon.view permission', function (): void {
    $response = $this->actingAs(adminWithout('system.horizon.view'))->get(route('horizon.index'));

    $response->assertForbidden();
});

it('shows the dashboard to admins with the system.horizon.view permission', function (): void {
    $response = $this->actingAs(adminUser())->get(route('horizon.index'));

    $response->assertOk();
});
