<?php

declare(strict_types=1);

use App\Models\User;

beforeEach(function (): void {
    $this->artisan('admin:sync-permissions')->assertSuccessful();
});

it('renders the settings pages inside the admin shell', function (string $path): void {
    $this->actingAs(adminUser())->withSession(['auth.password_confirmed_at' => time()]);

    visit($path)
        ->assertSee('Settings')
        ->assertSee('Users')
        ->assertSee('Audit log')
        ->assertDontSee('Repository')
        ->assertDontSee('Documentation')
        ->assertNoJavaScriptErrors();
})->with([
    'profile' => '/settings/profile',
    'password' => '/settings/password',
    'two-factor' => '/settings/two-factor',
    'passkeys' => '/settings/passkeys',
    'appearance' => '/settings/appearance',
]);

it('renders the settings pages for users without panel access', function (): void {
    $this->actingAs(User::factory()->create());

    visit('/settings/profile')
        ->assertSee('Settings')
        ->assertDontSee('Audit log')
        ->assertNoJavaScriptErrors();
});
