<?php

declare(strict_types=1);

use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    $this->artisan('admin:sync-permissions')->assertSuccessful();
});

function logViewerAdmin(): User
{
    $user = User::factory()->create();
    $user->assignRole('admin');

    return $user;
}

it('redirects guests to the login page', function (): void {
    $response = $this->get('/log-viewer');

    $response->assertRedirectToRoute('login');
});

it('forbids authenticated users without the admin role', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/log-viewer');

    $response->assertForbidden();
});

it('forbids admins without the system.logs.view permission', function (): void {
    Role::findByName('admin')->revokePermissionTo('system.logs.view');

    $response = $this->actingAs(logViewerAdmin())->get(route('log-viewer.index'));

    $response->assertForbidden();
});

it('shows the log viewer to admins with the system.logs.view permission', function (): void {
    $response = $this->actingAs(logViewerAdmin())->get(route('log-viewer.index'));

    $response->assertOk();
});
