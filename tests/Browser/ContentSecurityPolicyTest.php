<?php

declare(strict_types=1);

beforeEach(function (): void {
    $this->artisan('admin:sync-permissions')->assertSuccessful();
});

it('renders the app under its content security policy without violations', function (string $route): void {
    $this->actingAs(adminUser());

    visit(route($route))
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors();
})->with([
    'admin.dashboard.index',
    'admin.users.index',
    'admin.audit.index',
    'user-profile.edit',
]);

it('renders the login page under its content security policy without violations', function (): void {
    visit(route('login'))
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors();
});
