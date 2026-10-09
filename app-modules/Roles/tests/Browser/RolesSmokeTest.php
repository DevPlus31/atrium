<?php

declare(strict_types=1);

use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    $this->artisan('admin:sync-permissions')->assertSuccessful();
});

it('renders the roles index with seeded roles', function (): void {
    $this->actingAs(adminUser());

    $editor = Role::create(['name' => 'editor']);
    $editor->givePermissionTo(['users.view', 'users.update']);

    Role::create(['name' => 'support']);
    Role::create(['name' => 'auditor']);

    visit(route('admin.roles.index'))
        ->assertSee('Roles')
        ->assertSee('editor')
        ->assertSee('support')
        ->assertSee('auditor')
        ->assertNoJavaScriptErrors();
});

it('renders the roles create page', function (): void {
    $this->actingAs(adminUser());

    visit(route('admin.roles.create'))
        ->assertSee('Create role')
        ->assertSee('Permissions')
        ->assertNoJavaScriptErrors();
});

it('renders the edit page for a non-system role', function (): void {
    $this->actingAs(adminUser());

    $role = Role::create(['name' => 'editor']);
    $role->givePermissionTo('users.view');

    visit(route('admin.roles.edit', $role))
        ->assertSee('Edit role')
        ->assertSee('editor')
        ->assertNoJavaScriptErrors();
});
