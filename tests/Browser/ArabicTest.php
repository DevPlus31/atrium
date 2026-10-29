<?php

declare(strict_types=1);

use App\Models\User;

/**
 * Where the sidebar sits against the page: `start` when it is wholly on the
 * reading start (right in RTL), anything else when it overlaps the content.
 */
const SIDEBAR_SIDE = <<<'JS'
    (() => {
        const sidebar = document.querySelector('[data-slot="sidebar"] [data-sidebar="sidebar"]').getBoundingClientRect();
        const content = document.querySelector('[data-slot="sidebar-inset"]').getBoundingClientRect();
        const rtl = document.documentElement.dir === 'rtl';

        return (rtl ? sidebar.left >= content.right - 1 : sidebar.right <= content.left + 1) ? 'start' : 'overlapping';
    })()
    JS;

beforeEach(function (): void {
    $this->artisan('admin:sync-permissions')->assertSuccessful();
});

it('switches the interface to Arabic, right to left', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $this->actingAs($user);

    visit(route('appearance.edit'))
        ->click('#language')
        ->click('[role="option"]:has-text("العربية")')
        ->assertSee('اللغة والمنطقة')
        ->assertSee('إعدادات المظهر')
        ->assertScript('document.documentElement.dir', 'rtl')
        ->assertScript('document.documentElement.lang', 'ar')
        ->assertNoJavaScriptErrors();

    expect($user->refresh()->locale)->toBe('ar');
});

it('puts the sidebar on the reading start, clear of the content', function (?string $direction, string $expected): void {
    $this->actingAs(adminUser([
        'locale' => 'ar',
        'layout' => $direction === null ? [] : ['direction' => $direction],
    ]));

    visit(route('admin.users.index'))
        ->assertSee('المستخدمون')
        ->assertScript('document.documentElement.dir', $expected)
        ->assertScript(SIDEBAR_SIDE, 'start')
        ->assertNoJavaScriptErrors()
        ->screenshot(filename: 'arabic-users-index-'.$expected);
})->with([
    'from the locale' => [null, 'rtl'],
    'chosen left to right' => ['ltr', 'ltr'],
]);
