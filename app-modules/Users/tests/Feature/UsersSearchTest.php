<?php

declare(strict_types=1);

use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    $this->artisan('admin:sync-permissions')->assertSuccessful();
});

it('finds users by name or email and opens them for editing', function (string $term): void {
    $admin = adminUser(['name' => 'Ada Admin', 'email' => 'ada@example.com']);
    $grace = User::factory()->create(['name' => 'Grace Hopper', 'email' => 'grace@navy.example']);

    $this->actingAs($admin)
        ->getJson(route('search', ['q' => $term]))
        ->assertOk()
        ->assertJsonPath('groups.0.label', 'Users')
        ->assertJsonPath('groups.0.icon', 'users')
        ->assertJsonPath('groups.0.results', [[
            'title' => 'Grace Hopper',
            'description' => 'grace@navy.example',
            'url' => route('admin.users.edit', $grace),
        ]]);
})->with(['hopper', 'navy.example']);

it('opens the filtered list when the user cannot edit the match', function (): void {
    Role::findByName('admin')->revokePermissionTo('users.update');
    $admin = adminUser();
    User::factory()->create(['name' => 'Grace Hopper', 'email' => 'grace@navy.example']);

    $this->actingAs($admin)
        ->getJson(route('search', ['q' => 'hopper']))
        ->assertJsonPath('groups.0.results.0.url', route('admin.users.index', ['filter' => ['search' => 'grace@navy.example']]));
});

it('finds no users without users.view', function (): void {
    Role::findByName('admin')->revokePermissionTo('users.view');
    $admin = adminUser();
    User::factory()->create(['name' => 'Grace Hopper']);

    $this->actingAs($admin)
        ->getJson(route('search', ['q' => 'hopper']))
        ->assertJsonMissingPath('groups.0');
});
