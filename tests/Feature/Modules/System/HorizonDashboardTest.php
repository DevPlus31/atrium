<?php

declare(strict_types=1);

use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    $this->artisan('admin:sync-permissions')->assertSuccessful();
});

function horizonAdmin(): User
{
    $user = User::factory()->create();
    $user->assignRole('admin');

    return $user;
}

it('redirects guests to the login page', function (): void {
    $response = $this->get('/horizon');

    $response->assertRedirectToRoute('login');
});

it('forbids authenticated users without the admin role', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/horizon');

    $response->assertForbidden();
});

it('forbids admins without the system.horizon.view permission', function (): void {
    Role::findByName('admin')->revokePermissionTo('system.horizon.view');

    $response = $this->actingAs(horizonAdmin())->get(route('horizon.index'));

    $response->assertForbidden();
});

it('shows the dashboard to admins with the system.horizon.view permission', function (): void {
    $response = $this->actingAs(horizonAdmin())->get(route('horizon.index'));

    $response->assertOk();
});
