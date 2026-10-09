<?php

declare(strict_types=1);

use App\Models\User;

beforeEach(function (): void {
    $this->artisan('admin:sync-permissions')->assertSuccessful();
});

it('keeps admin pages out of reach of the back button after logout', function (): void {
    $this->actingAs(adminUser());
    User::factory()->create(['email' => 'private.person@example.com']);

    visit(route('admin.users.index'))
        ->assertSee('private.person@example.com')
        ->click('[data-test="sidebar-menu-button"]')
        ->click('[data-test="logout-button"]')
        ->assertPathIs('/login')
        ->back()
        ->assertPathIs('/login')
        ->assertSee('Log in')
        ->assertDontSee('private.person@example.com');
});
