<?php

declare(strict_types=1);

use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    $this->artisan('admin:sync-permissions')->assertSuccessful();
});

function pulseAdmin(): User
{
    $user = User::factory()->create();
    $user->assignRole('admin');

    return $user;
}

it('redirects guests to the login page', function (): void {
    $response = $this->get('/pulse');

    $response->assertRedirectToRoute('login');
});

it('forbids authenticated users without the admin role', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/pulse');

    $response->assertForbidden();
});

it('forbids admins without the system.pulse.view permission', function (): void {
    Role::findByName('admin')->revokePermissionTo('system.pulse.view');

    $response = $this->actingAs(pulseAdmin())->get(route('pulse'));

    $response->assertForbidden();
});

it('shows the dashboard to admins with the system.pulse.view permission', function (): void {
    $response = $this->actingAs(pulseAdmin())->get(route('pulse'));

    $response->assertOk();
});
