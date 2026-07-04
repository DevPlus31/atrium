<?php

declare(strict_types=1);

use App\Models\User;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    $this->artisan('admin:sync-permissions')->assertSuccessful();
});

function usersExportAdmin(): User
{
    $user = User::factory()->create();
    $user->assignRole('admin');

    return $user;
}

it('redirects guests to the login page', function (): void {
    $response = $this->get('/admin/users/export');

    $response->assertRedirectToRoute('login');
});

it('forbids authenticated users without the admin role', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('admin.users.export'));

    $response->assertForbidden();
});

it('forbids admins without the users.export permission', function (): void {
    Role::findByName('admin')->revokePermissionTo('users.export');

    $response = $this->actingAs(usersExportAdmin())->get(route('admin.users.export'));

    $response->assertForbidden();
});

it('streams a csv of all users', function (): void {
    $admin = usersExportAdmin();
    $user = User::factory()->create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);

    $response = $this->actingAs($admin)->get(route('admin.users.export'));

    $response->assertOk()
        ->assertDownload('users.csv');

    $csv = $response->streamedContent();

    expect($csv)->toContain('id,name,email,email_verified_at,roles,created_at')
        ->toContain('jane@example.com')
        ->toContain($admin->email);
});

it('exports only the currently filtered rows', function (): void {
    $admin = usersExportAdmin();
    User::factory()->create(['name' => 'Alice Wonder', 'email' => 'alice@example.com']);
    User::factory()->create(['name' => 'Bob Builder', 'email' => 'bob@example.com']);

    $response = $this->actingAs($admin)->get(route('admin.users.export', ['filter' => ['search' => 'alice']]));

    $csv = $response->streamedContent();

    expect($csv)->toContain('alice@example.com')
        ->not->toContain('bob@example.com')
        ->not->toContain($admin->email);
});

it('logs the export activity with the applied filters', function (): void {
    $admin = usersExportAdmin();

    $this->actingAs($admin)
        ->get(route('admin.users.export', ['filter' => ['verified' => 'yes']]))
        ->assertOk();

    $activity = Activity::query()->where('event', 'exported')->sole();

    expect($activity->causer_id)->toBe($admin->id)
        ->and($activity->getProperty('filter'))->toBe(['verified' => 'yes']);
});
