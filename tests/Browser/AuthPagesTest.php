<?php

declare(strict_types=1);

use App\Models\User;
use Pest\Browser\Api\AwaitableWebpage;

/**
 * Guests carry their display preferences in plain cookies; set them in the
 * page and reload so the server stamps the first paint with them.
 */
function visitAuthPageWith(string $url, string $theme, string $appearance, string $direction = 'ltr'): AwaitableWebpage
{
    $layout = json_encode(['direction' => $direction], JSON_THROW_ON_ERROR);

    $page = visit($url);

    $page->script(sprintf(
        "document.cookie = 'theme=%s; path=/'; document.cookie = 'appearance=%s; path=/'; document.cookie = 'layout=%s; path=/';",
        $theme,
        $appearance,
        rawurlencode($layout),
    ));

    return $page->navigate($url);
}

it('renders the login page in every preset and appearance', function (string $theme, string $appearance, string $direction): void {
    visitAuthPageWith(route('login'), $theme, $appearance, $direction)
        ->assertSee('Welcome back')
        ->assertSee('The open hall for your operations.')
        ->assertSee('Create an account')
        ->assertScript('document.documentElement.dir', $direction)
        ->assertNoJavaScriptErrors()
        ->screenshot(filename: sprintf('auth-login-%s-%s-%s', $theme, $appearance, $direction));
})->with([
    'default light' => ['default', 'light', 'ltr'],
    'default dark' => ['default', 'dark', 'ltr'],
    'ember light' => ['ember', 'light', 'ltr'],
    'ember dark' => ['ember', 'dark', 'ltr'],
    'contrast light' => ['contrast', 'light', 'ltr'],
    'contrast dark rtl' => ['contrast', 'dark', 'rtl'],
]);

it('renders the register page', function (): void {
    visit(route('register'))
        ->assertSee('Create your account')
        ->assertSee('Already have an account?')
        ->assertNoJavaScriptErrors()
        ->screenshot(filename: 'auth-register');
});

it('logs an admin into the panel', function (): void {
    $this->artisan('admin:sync-permissions')->assertSuccessful();
    adminUser(['email' => 'ada@example.com']);
    User::query()->where('email', 'ada@example.com')->update([
        'two_factor_secret' => null,
        'two_factor_recovery_codes' => null,
        'two_factor_confirmed_at' => null,
    ]);

    visit(route('login'))
        ->type('email', 'ada@example.com')
        ->type('password', 'password')
        ->click('#remember')
        ->click('[data-test="login-button"]')
        ->assertPathIs('/admin/dashboard')
        ->assertNoJavaScriptErrors();

    // The controlled "Remember me" checkbox still reaches the server.
    expect(User::query()->where('email', 'ada@example.com')->value('remember_token'))->not->toBeNull();
});

it('signs up a new user who is asked to verify their email, not shown a 403', function (): void {
    visit(route('register'))
        ->type('name', 'New Person')
        ->type('email', 'new.person@example.com')
        ->type('password', 'a-Strong-passw0rd!')
        ->type('password_confirmation', 'a-Strong-passw0rd!')
        ->click('[data-test="register-user-button"]')
        ->assertPathIs('/verify-email')
        ->assertSee('Verify')
        ->assertNoJavaScriptErrors();

    expect(User::query()->where('email', 'new.person@example.com')->exists())->toBeTrue();
});

it('collapses the panel to a band on phones without horizontal scrolling', function (): void {
    visit(route('login'))
        ->on()->iPhone15()
        ->assertSee('Welcome back')
        ->assertDontSee('The open hall for your operations.')
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth', true)
        ->assertNoJavaScriptErrors()
        ->screenshot(filename: 'auth-login-mobile');
});

it('frames the other authentication pages with the same layout', function (string $route, ?string $as): void {
    if ($as === 'unverified') {
        $this->actingAs(User::factory()->unverified()->withoutTwoFactor()->create());
    }

    if ($as === 'verified') {
        $this->actingAs(User::factory()->withoutTwoFactor()->create());
    }

    visit(route($route))
        ->assertSee('The open hall for your operations.')
        ->assertNoJavaScriptErrors()
        ->screenshot(filename: 'auth-'.str_replace('.', '-', $route));
})->with([
    'forgot password' => ['password.request', null],
    'verify email' => ['verification.notice', 'unverified'],
    'confirm password' => ['password.confirm', 'verified'],
]);
