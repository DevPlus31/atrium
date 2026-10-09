<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    $this->artisan('admin:sync-permissions')->assertSuccessful();
});

it('denies super-admins deleting a system role', function (string $name): void {
    $role = Role::findOrCreate($name);

    $this->actingAs(superAdminUser())
        ->delete(route('admin.roles.destroy', $role))
        ->assertForbidden();

    expect(Role::query()->whereKey($role->getKey())->exists())->toBeTrue();
})->with(['admin', 'super-admin']);

it('denies super-admins deleting their own account from the panel', function (): void {
    $superAdmin = superAdminUser();

    $this->actingAs($superAdmin)
        ->delete(route('admin.users.destroy', $superAdmin))
        ->assertForbidden();

    $this->assertModelExists($superAdmin);
});

it('denies super-admins impersonating themselves or another super-admin', function (): void {
    $superAdmin = superAdminUser();

    expect($superAdmin->can('impersonate', $superAdmin))->toBeFalse()
        ->and($superAdmin->can('impersonate', superAdminUser()))->toBeFalse();
});

it('denies super-admins deleting log files', function (): void {
    $gate = Gate::forUser(superAdminUser());

    expect($gate->allows('deleteLogFile'))->toBeFalse()
        ->and($gate->allows('deleteLogFolder'))->toBeFalse()
        ->and($gate->allows('viewLogViewer'))->toBeTrue();
});

it('still grants super-admins every declared permission', function (): void {
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole(Role::findOrCreate(User::SUPER_ADMIN_ROLE));

    expect($superAdmin->can('users.delete'))->toBeTrue()
        ->and($superAdmin->can('create', Role::class))->toBeTrue();
});
