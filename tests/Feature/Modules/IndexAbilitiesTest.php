<?php

declare(strict_types=1);

use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    $this->withoutVite();

    $this->artisan('admin:sync-permissions')->assertSuccessful();
});

it('ships page-level abilities that follow the admin permissions', function (string $route, string $ability, string $permission): void {
    $admin = adminUser();

    $this->actingAs($admin)->get(route($route))
        ->assertInertia(fn ($page) => $page->where('can.'.$ability, true));

    Role::findByName('admin')->revokePermissionTo($permission);

    $this->actingAs($admin->refresh())->get(route($route))
        ->assertInertia(fn ($page) => $page->where('can.'.$ability, false));
})->with([
    'roles create' => ['admin.roles.index', 'create', 'roles.create'],
    'users create' => ['admin.users.index', 'create', 'users.create'],
    'users export' => ['admin.users.index', 'export', 'users.export'],
]);
