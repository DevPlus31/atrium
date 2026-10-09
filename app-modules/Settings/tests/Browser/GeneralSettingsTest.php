<?php

declare(strict_types=1);

use App\Settings\GeneralSettings;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    $this->artisan('admin:sync-permissions')->assertSuccessful();
});

it('closes sign-ups from the general settings', function (): void {
    $this->actingAs(adminUser());

    visit(route('admin.settings.general.edit'))
        ->assertSee('Allow sign-ups')
        ->click('#registration_open')
        ->click('Save')
        ->assertSee('Settings saved.')
        ->assertNoJavaScriptErrors();

    expect(resolve(GeneralSettings::class)->refresh()->registration_open)->toBeFalse();

    Auth::logout();

    visit(route('login'))->assertDontSee('Create an account');
});

it('shows the uploaded logo instead of the built-in mark', function (): void {
    Storage::fake('public');
    Storage::disk('public')->put('branding/logo.png', (string) file_get_contents(base_path('tests/Fixtures/images/avatar.png')));
    $settings = resolve(GeneralSettings::class);
    $settings->logo_path = 'branding/logo.png';
    $settings->save();

    $this->actingAs(adminUser());

    visit(route('admin.settings.general.edit'))
        ->assertPresent('[data-slot="sidebar"] img[src*="branding/logo.png"]')
        ->assertNoJavaScriptErrors();
});
