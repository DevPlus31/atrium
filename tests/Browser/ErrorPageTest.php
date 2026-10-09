<?php

declare(strict_types=1);

it('shows missing pages as the branded error page', function (): void {
    visit('/this-page-does-not-exist')
        ->assertSee('404')
        ->assertSee('Page not found')
        ->assertSee(config('app.name'))
        ->assertNoJavaScriptErrors()
        ->screenshot(filename: 'error-404');
});

it('takes the visitor home from the error page', function (): void {
    visit('/this-page-does-not-exist')
        ->click('Go to home')
        ->assertPathIs('/login')
        ->assertNoJavaScriptErrors();
});
