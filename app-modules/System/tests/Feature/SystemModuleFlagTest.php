<?php

declare(strict_types=1);

use App\Models\User;
use Laravel\Pennant\Feature;

beforeEach(function (): void {
    $this->artisan('admin:sync-permissions')->assertSuccessful();
});

it('hides the system tools when the system module is disabled', function (string $uri): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)->get($uri)->assertSuccessful();

    Feature::for($admin)->deactivate('module:system');

    $this->actingAs($admin)->get($uri)->assertNotFound();
})->with([
    'pulse' => '/pulse',
    'horizon' => '/horizon',
    'log viewer' => '/log-viewer',
    'log viewer api' => '/log-viewer/api/folders',
]);
