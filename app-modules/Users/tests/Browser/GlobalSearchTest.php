<?php

declare(strict_types=1);

use App\Models\User;

beforeEach(function (): void {
    $this->artisan('admin:sync-permissions')->assertSuccessful();
});

it('finds a user from the command palette and opens them', function (): void {
    $this->actingAs(adminUser());
    $grace = User::factory()->create(['name' => 'Grace Hopper', 'email' => 'grace@navy.example']);

    visit(route('admin.dashboard.index'))
        ->click('Search')
        ->type('[cmdk-input]', 'hopper')
        ->assertSee('Grace Hopper')
        ->assertSee('grace@navy.example')
        ->click('[data-test="search-result"]')
        ->assertPathIs(sprintf('/admin/users/%s/edit', $grace->id))
        ->assertNoJavaScriptErrors();
});
