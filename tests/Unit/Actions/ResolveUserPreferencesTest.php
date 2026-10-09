<?php

declare(strict_types=1);

use App\Actions\ResolveUserPreferences;
use App\Enums\Appearance;
use App\Enums\ThemePreset;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * @param  array<string, string>  $cookies
 */
function preferencesRequest(array $cookies = [], ?User $user = null): Request
{
    $request = Request::create('/', 'GET', cookies: $cookies);
    $request->setUserResolver(fn (): ?User => $user);

    return $request;
}

it('falls back to the defaults for a guest without cookies', function (): void {
    $preferences = new ResolveUserPreferences()->handle(preferencesRequest());

    expect($preferences['appearance'])->toBe(Appearance::System)
        ->and($preferences['theme'])->toBe(ThemePreset::Default)
        ->and($preferences['layout']->toArray())->toBe(ResolveUserPreferences::DEFAULT_LAYOUT)
        ->and($preferences['locale'])->toBe(config('app.locale'))
        ->and($preferences['timezone'])->toBeNull();
});

it("reads a guest's choices from cookies, ignoring invalid ones", function (): void {
    $preferences = new ResolveUserPreferences()->handle(preferencesRequest([
        'appearance' => 'dark',
        'theme' => 'not-a-theme',
        'locale' => 'fr',
        'layout' => json_encode(['nav_placement' => 'topbar', 'direction' => 'sideways'], JSON_THROW_ON_ERROR),
    ]));

    expect($preferences['appearance'])->toBe(Appearance::Dark)
        ->and($preferences['theme'])->toBe(ThemePreset::Default)
        ->and($preferences['locale'])->toBe('fr')
        ->and($preferences['layout']->nav_placement->value)->toBe('topbar')
        ->and($preferences['layout']->direction->value)->toBe('ltr');
});

it('prefers what a signed-in user saved over the cookies', function (): void {
    $user = User::factory()->create([
        'appearance' => Appearance::Light,
        'theme' => ThemePreset::Ember,
        'locale' => 'en',
        'timezone' => 'Europe/Paris',
        'layout' => ['content_width' => 'boxed'],
    ]);

    $preferences = new ResolveUserPreferences()->handle(preferencesRequest([
        'appearance' => 'dark',
        'locale' => 'fr',
        'layout' => json_encode(['content_width' => 'fluid', 'header' => 'static'], JSON_THROW_ON_ERROR),
    ], $user));

    expect($preferences['appearance'])->toBe(Appearance::Light)
        ->and($preferences['theme'])->toBe(ThemePreset::Ember)
        ->and($preferences['locale'])->toBe('en')
        ->and($preferences['timezone'])->toBe('Europe/Paris')
        ->and($preferences['layout']->content_width->value)->toBe('boxed')
        ->and($preferences['layout']->header->value)->toBe('static');
});

it('ignores a saved locale that is no longer offered', function (): void {
    $user = User::factory()->create(['locale' => 'xx']);

    expect(new ResolveUserPreferences()->handle(preferencesRequest(user: $user))['locale'])->toBe(config('app.locale'));
});
