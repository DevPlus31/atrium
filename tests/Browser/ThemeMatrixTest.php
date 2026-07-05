<?php

declare(strict_types=1);

use App\Models\User;

beforeEach(function (): void {
    $this->artisan('admin:sync-permissions')->assertSuccessful();
});

/**
 * @param  array<string, mixed>  $attributes
 */
function matrixAdmin(array $attributes = []): User
{
    $user = User::factory()->create($attributes);
    $user->assignRole('admin');

    return $user;
}

it('renders the dashboard in every preset and appearance', function (string $theme, string $appearance): void {
    $this->actingAs(matrixAdmin([
        'theme' => $theme,
        'appearance' => $appearance,
    ]));

    $page = visit(route('admin.dashboard.index'));

    $page->assertSee('Dashboard')
        ->assertNoJavaScriptErrors()
        ->screenshot(filename: "dashboard-{$theme}-{$appearance}");
})
    ->with(['default', 'ember', 'contrast'])
    ->with(['light', 'dark']);

it('renders the reference surfaces in each layout variant', function (array $layout): void {
    $this->actingAs(matrixAdmin([
        'theme' => 'ember',
        'appearance' => 'dark',
        'layout' => $layout,
    ]));

    $name = implode('-', array_map(
        static fn (string $value): string => str_replace('sidebar-', '', $value),
        $layout,
    ));

    visit(route('admin.users.index'))
        ->assertSee('Users')
        ->assertNoJavaScriptErrors()
        ->screenshot(filename: "users-index-{$name}");

    visit(route('admin.users.create'))
        ->assertSee('Create')
        ->assertNoJavaScriptErrors()
        ->screenshot(filename: "users-create-{$name}");
})->with([
    'sidebar-left icon' => [['nav_placement' => 'sidebar-left', 'sidebar_collapsible' => 'icon']],
    'sidebar-left offcanvas' => [['nav_placement' => 'sidebar-left', 'sidebar_collapsible' => 'offcanvas']],
    'sidebar-left none' => [['nav_placement' => 'sidebar-left', 'sidebar_collapsible' => 'none']],
    'sidebar-right icon' => [['nav_placement' => 'sidebar-right', 'sidebar_collapsible' => 'icon']],
    'sidebar floating' => [['nav_placement' => 'sidebar-left', 'sidebar_variant' => 'floating']],
    'sidebar inset' => [['nav_placement' => 'sidebar-left', 'sidebar_variant' => 'inset']],
    'topbar' => [['nav_placement' => 'topbar']],
    'boxed' => [['nav_placement' => 'sidebar-left', 'content_width' => 'boxed']],
    'static header' => [['nav_placement' => 'sidebar-left', 'header' => 'static']],
    'rtl' => [['nav_placement' => 'sidebar-left', 'direction' => 'rtl']],
]);

it('stamps the first paint with the persisted theme, appearance, and direction', function (): void {
    $this->actingAs(matrixAdmin([
        'theme' => 'contrast',
        'appearance' => 'dark',
        'layout' => ['direction' => 'rtl'],
    ]));

    $response = $this->get(route('admin.dashboard.index'));

    $response->assertOk();

    $html = $response->getContent();

    expect($html)->toContain('data-theme="contrast"')
        ->and($html)->toContain('dir="rtl"')
        ->and($html)->toMatch('/<html[^>]*class="[^"]*dark/');
});
