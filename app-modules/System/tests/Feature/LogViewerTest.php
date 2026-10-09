<?php

declare(strict_types=1);

beforeEach(function (): void {
    $this->artisan('admin:sync-permissions')->assertSuccessful();
});

it('keeps guests and non-admins out', function (): void {
    assertAdminOnly('get', route('log-viewer.index'));
});

it('forbids admins without the system.logs.view permission', function (): void {
    $response = $this->actingAs(adminWithout('system.logs.view'))->get(route('log-viewer.index'));

    $response->assertForbidden();
});

it('shows the log viewer to admins with the system.logs.view permission', function (): void {
    $response = $this->actingAs(adminUser())->get(route('log-viewer.index'));

    $response->assertOk();
});
