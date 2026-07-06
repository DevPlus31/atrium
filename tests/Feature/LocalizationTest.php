<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Lang;

beforeEach(function (): void {
    $this->withoutVite();
});

it('stamps the first paint with the locale from the cookie for guests', function (): void {
    config()->set('app.available_locales', ['en' => 'English', 'fr' => 'Français']);

    $response = $this->withUnencryptedCookie('locale', 'fr')->get('/');

    $response->assertOk();

    expect(app()->getLocale())->toBe('fr')
        ->and($response->getContent())->toContain('<html lang="fr"');
});

it('falls back to the default locale when the cookie locale is unavailable', function (): void {
    $response = $this->withUnencryptedCookie('locale', 'xx')->get('/');

    $response->assertOk();

    expect(app()->getLocale())->toBe('en')
        ->and($response->getContent())->toContain('<html lang="en"');
});

it('prefers the authenticated user locale column over the cookie', function (): void {
    config()->set('app.available_locales', ['en' => 'English', 'fr' => 'Français']);

    $user = User::factory()->create(['locale' => 'fr']);

    $response = $this->actingAs($user)
        ->withUnencryptedCookie('locale', 'en')
        ->get(route('dashboard'));

    $response->assertOk();

    expect(app()->getLocale())->toBe('fr')
        ->and($response->getContent())->toContain('<html lang="fr"');
});

it('shares the locale and the available locales with the frontend', function (): void {
    config()->set('app.available_locales', ['en' => 'English', 'fr' => 'Français']);

    $user = User::factory()->create(['locale' => 'fr']);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->where('locale', 'fr')
                ->where('locales', ['en' => 'English', 'fr' => 'Français']),
        );
});

it('serves server-sent nav labels in the active locale', function (): void {
    config()->set('app.available_locales', ['en' => 'English', 'fr' => 'Français']);

    Lang::addLines(['*.Users' => 'Utilisateurs'], 'fr');

    $this->artisan('admin:sync-permissions')->assertSuccessful();

    $user = User::factory()->create(['locale' => 'fr']);
    $user->assignRole('admin');

    $response = $this->actingAs($user)->get(route('admin.dashboard.index'));

    $response->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->where('nav', fn ($nav) => collect($nav)->contains(
                    fn (array $item): bool => $item['label'] === 'Utilisateurs',
                )),
        );
});
