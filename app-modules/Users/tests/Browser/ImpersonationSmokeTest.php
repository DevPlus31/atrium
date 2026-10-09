<?php

declare(strict_types=1);

use App\Models\User;

beforeEach(function (): void {
    $this->artisan('admin:sync-permissions')->assertSuccessful();
});

it('impersonates a user from the users index and leaves again', function (): void {
    $admin = adminUser(['name' => 'Ada Admin']);
    User::factory()->create(['name' => 'Jane Target']);

    $this->actingAs($admin);

    visit(route('admin.users.index'))
        ->assertSee('Jane Target')
        ->click('tr:has-text("Jane Target") [data-test="row-actions"]')
        ->click('Impersonate')
        ->assertPathIs('/settings/profile')
        ->assertSee('Jane Target')
        ->assertSee('Ada Admin')
        ->click('[data-test="leave-impersonation"]')
        ->assertPathIs('/admin/dashboard')
        ->assertNoJavaScriptErrors();
});
