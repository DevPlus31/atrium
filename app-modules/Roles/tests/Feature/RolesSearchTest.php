<?php

declare(strict_types=1);

use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    $this->artisan('admin:sync-permissions')->assertSuccessful();
});

it('finds roles by name', function (): void {
    $editor = Role::findOrCreate('editor');

    $this->actingAs(adminUser())
        ->getJson(route('search', ['q' => 'edit']))
        ->assertOk()
        ->assertJsonPath('groups.0.label', 'Roles')
        ->assertJsonPath('groups.0.results', [[
            'title' => 'editor',
            'description' => null,
            'url' => route('admin.roles.edit', $editor),
        ]]);
});

it('opens the filtered list for roles only a super-admin may change', function (): void {
    Role::findOrCreate(User::SUPER_ADMIN_ROLE);

    $this->actingAs(adminUser())
        ->getJson(route('search', ['q' => 'super-adm']))
        ->assertJsonPath('groups.0.label', 'Roles')
        ->assertJsonPath('groups.0.results.0.url', route('admin.roles.index', ['filter' => ['search' => User::SUPER_ADMIN_ROLE]]));
});
