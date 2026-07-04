<?php

declare(strict_types=1);

use App\Modules\PermissionRegistry;

it('declares the system permissions with the admin role as default', function (): void {
    $permissions = $this->app->make(PermissionRegistry::class);

    expect($permissions->permissions())->toContain(
        'system.pulse.view',
        'system.horizon.view',
        'system.logs.view',
    )
        ->and($permissions->roleAssignments()['system.pulse.view'])->toBe(['admin'])
        ->and($permissions->roleAssignments()['system.horizon.view'])->toBe(['admin'])
        ->and($permissions->roleAssignments()['system.logs.view'])->toBe(['admin']);
});

it('registers the tool routes outside the admin module group', function (): void {
    expect(route('pulse', absolute: false))->toBe('/pulse')
        ->and(route('horizon.index', absolute: false))->toBe('/horizon')
        ->and(route('log-viewer.index', absolute: false))->toBe('/log-viewer');
});
