<?php

declare(strict_types=1);

beforeEach(function (): void {
    $this->artisan('admin:sync-permissions')->assertSuccessful();
});

it('keeps guests and non-admins out', function (): void {
    assertAdminOnly('get', route('pulse'));
});

it('forbids admins without the system.pulse.view permission', function (): void {
    $response = $this->actingAs(adminWithout('system.pulse.view'))->get(route('pulse'));

    $response->assertForbidden();
});

it('shows the dashboard to admins with the system.pulse.view permission', function (): void {
    $response = $this->actingAs(adminUser())->get(route('pulse'));

    $response->assertOk();
});
