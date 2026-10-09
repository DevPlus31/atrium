<?php

declare(strict_types=1);

use App\Enums\Appearance;
use App\Enums\ThemePreset;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

it('requires authentication', function (): void {
    $response = $this->patch(route('preferences.update'), [
        'appearance' => 'dark',
    ]);

    $response->assertRedirectToRoute('login');
});

it('validates the appearance, theme, and layout options', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->fromRoute('dashboard')
        ->patch(route('preferences.update'), [
            'appearance' => 'sepia',
            'theme' => 'unknown',
            'layout' => ['nav_placement' => 'floating'],
        ]);

    $response->assertSessionHasErrors(['appearance', 'theme', 'layout.nav_placement']);
});

it('validates the locale against the available locales', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->fromRoute('dashboard')
        ->patch(route('preferences.update'), [
            'locale' => 'xx',
        ]);

    $response->assertSessionHasErrors(['locale']);
});

it('persists the locale and re-issues the js-readable locale cookie', function (): void {
    config()->set('app.available_locales', ['en' => 'English', 'fr' => 'Français']);

    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->fromRoute('dashboard')
        ->patch(route('preferences.update'), [
            'locale' => 'fr',
        ]);

    expect($user->refresh()->locale)->toBe('fr');

    $response->assertRedirectToRoute('dashboard')
        ->assertCookie('locale', 'fr', encrypted: false);
});

it('rejects unknown layout keys', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->fromRoute('dashboard')
        ->patch(route('preferences.update'), [
            'layout' => ['free_form' => 'anything'],
        ]);

    $response->assertSessionHasErrors(['layout']);
});

it('persists preferences and re-issues js-readable cookies', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->fromRoute('dashboard')
        ->patch(route('preferences.update'), [
            'appearance' => 'dark',
            'theme' => 'ember',
            'layout' => ['direction' => 'rtl'],
        ]);

    $user->refresh();

    expect($user->appearance)->toBe(Appearance::Dark)
        ->and($user->theme)->toBe(ThemePreset::Ember)
        ->and($user->layout)->toBe(['direction' => 'rtl']);

    $response->assertRedirectToRoute('dashboard')
        ->assertCookie('appearance', 'dark', encrypted: false)
        ->assertCookie('theme', 'ember', encrypted: false)
        ->assertCookie('layout', json_encode([
            'nav_placement' => 'sidebar-left',
            'sidebar_variant' => 'sidebar',
            'sidebar_collapsible' => 'icon',
            'content_width' => 'fluid',
            'header' => 'sticky',
            'direction' => 'rtl',
        ], JSON_THROW_ON_ERROR), encrypted: false);
});

it('updates a single preference without dropping stored layout options', function (): void {
    $user = User::factory()->create([
        'layout' => ['content_width' => 'boxed'],
    ]);

    $response = $this->actingAs($user)
        ->fromRoute('dashboard')
        ->patch(route('preferences.update'), [
            'layout' => ['direction' => 'rtl'],
        ]);

    $response->assertRedirectToRoute('dashboard');

    expect($user->refresh()->layout)->toBe([
        'content_width' => 'boxed',
        'direction' => 'rtl',
    ]);
});

it('persists a chosen timezone and clears it back to automatic', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->patch(route('preferences.update'), ['timezone' => 'Europe/Paris'])->assertRedirect();

    expect($user->refresh()->timezone)->toBe('Europe/Paris');

    $this->actingAs($user)->patch(route('preferences.update'), ['timezone' => null])->assertRedirect();

    expect($user->refresh()->timezone)->toBeNull();
});

it('rejects unknown timezones', function (): void {
    $user = User::factory()->create(['timezone' => 'Europe/Paris']);

    $this->actingAs($user)
        ->patch(route('preferences.update'), ['timezone' => 'Mars/Olympus_Mons'])
        ->assertSessionHasErrors('timezone');

    expect($user->refresh()->timezone)->toBe('Europe/Paris');
});

it('shares the chosen timezone with every page', function (): void {
    $this->withoutVite();

    $this->actingAs(User::factory()->create(['timezone' => 'Asia/Tokyo']))
        ->get(route('user-profile.edit'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page->where('timezone', 'Asia/Tokyo'));
});
