<?php

declare(strict_types=1);

use App\Models\User;

beforeEach(function (): void {
    $this->artisan('admin:sync-permissions')->assertSuccessful();
});

it('lands a regular user on their home after signing in', function (): void {
    $user = User::factory()->withoutTwoFactor()->create([
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
    ]);
    activity('users')->performedOn($user)->causedBy(adminUser(['name' => 'Ada Admin']))->event('updated')->log('updated');

    visit(route('login'))
        ->type('email', 'jane@example.com')
        ->type('password', 'password')
        ->click('[data-test="login-button"]')
        ->assertPathIs('/home')
        ->assertSee('Welcome, Jane')
        ->assertSee('Ada Admin')
        ->assertNoJavaScriptErrors();
});

it('shows the member home in the shell with its own nav, on phones too', function (): void {
    $this->actingAs(User::factory()->create(['name' => 'Jane Doe']));

    visit(route('member.dashboard'))
        ->assertSee('Two-factor authentication')
        ->assertSee('No one has changed your account.')
        ->assertDontSee('Users')
        ->assertNoJavaScriptErrors()
        ->screenshot(filename: 'member-home-desktop');

    visit(route('member.dashboard'))
        ->on()->iPhone15()
        ->assertSee('Welcome, Jane')
        ->assertSee('Sign-in security')
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth', true)
        ->screenshot(filename: 'member-home-mobile');
});
