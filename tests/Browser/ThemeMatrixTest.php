<?php

declare(strict_types=1);

beforeEach(function (): void {
    $this->artisan('admin:sync-permissions')->assertSuccessful();
});

it('renders the dashboard in every preset and appearance', function (string $theme, string $appearance): void {
    $this->actingAs(adminUser([
        'theme' => $theme,
        'appearance' => $appearance,
    ]));

    $page = visit(route('admin.dashboard.index'));

    $page->assertSee('Dashboard')
        ->assertScript('document.documentElement.dataset.theme', $theme === 'default' ? null : $theme)
        ->assertScript("document.documentElement.classList.contains('dark')", $appearance === 'dark')
        ->assertNoJavaScriptErrors()
        ->screenshot(filename: sprintf('dashboard-%s-%s', $theme, $appearance));
})
    ->with(['default', 'ember', 'contrast'])
    ->with(['light', 'dark']);

it('loads the deferred dashboard widgets', function (): void {
    $this->actingAs(adminUser());

    visit(route('admin.dashboard.index'))
        ->assertSee('Total users')
        ->assertSee('Recent users')
        ->assertNoJavaScriptErrors();
});

it('renders the reference surfaces in each layout variant and preset', function (array $layout, string $theme, string $appearance): void {
    $this->actingAs(adminUser([
        'theme' => $theme,
        'appearance' => $appearance,
        'layout' => $layout,
    ]));

    $name = implode('-', [
        ...array_map(static fn (string $value): string => str_replace('sidebar-', '', $value), $layout),
        $theme,
        $appearance,
    ]);

    $direction = $layout['direction'] ?? 'ltr';

    visit(route('admin.users.index'))
        ->assertSee('Users')
        ->assertScript('document.documentElement.dataset.theme', $theme === 'default' ? null : $theme)
        ->assertScript('document.documentElement.dir', $direction)
        ->assertNoJavaScriptErrors()
        ->screenshot(filename: 'users-index-'.$name);

    visit(route('admin.users.create'))
        ->assertPathIs('/admin/users/create')
        ->assertScript('document.documentElement.dir', $direction)
        ->assertNoJavaScriptErrors()
        ->screenshot(filename: 'users-create-'.$name);
})->with([
    'sidebar-left icon' => [['nav_placement' => 'sidebar-left', 'sidebar_collapsible' => 'icon']],
    'sidebar-left offcanvas' => [['nav_placement' => 'sidebar-left', 'sidebar_collapsible' => 'offcanvas']],
    'sidebar-left none' => [['nav_placement' => 'sidebar-left', 'sidebar_collapsible' => 'none']],
    'sidebar-right icon' => [['nav_placement' => 'sidebar-right', 'sidebar_collapsible' => 'icon']],
    'sidebar-right offcanvas' => [['nav_placement' => 'sidebar-right', 'sidebar_collapsible' => 'offcanvas']],
    'sidebar-right none' => [['nav_placement' => 'sidebar-right', 'sidebar_collapsible' => 'none']],
    'sidebar floating' => [['nav_placement' => 'sidebar-left', 'sidebar_variant' => 'floating']],
    'sidebar inset' => [['nav_placement' => 'sidebar-left', 'sidebar_variant' => 'inset']],
    'topbar' => [['nav_placement' => 'topbar']],
    'topbar boxed rtl' => [['nav_placement' => 'topbar', 'content_width' => 'boxed', 'direction' => 'rtl']],
    'boxed' => [['nav_placement' => 'sidebar-left', 'content_width' => 'boxed']],
    'static header' => [['nav_placement' => 'sidebar-left', 'header' => 'static']],
    'rtl' => [['nav_placement' => 'sidebar-left', 'direction' => 'rtl']],
])->with([
    'default light' => ['default', 'light'],
    'ember dark' => ['ember', 'dark'],
    'contrast light' => ['contrast', 'light'],
]);

it('stamps the first paint with the persisted theme, appearance, and direction', function (): void {
    $this->actingAs(adminUser([
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
