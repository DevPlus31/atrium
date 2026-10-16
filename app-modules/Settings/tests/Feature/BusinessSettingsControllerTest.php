<?php

declare(strict_types=1);

use App\Enums\DistanceUnit;
use App\Models\User;
use App\Settings\BusinessSettings;
use Inertia\Testing\AssertableInertia;
use Spatie\Activitylog\Models\Activity;

beforeEach(function (): void {
    $this->artisan('admin:sync-permissions')->assertSuccessful();
});

/**
 * @return array<string, string>
 */
function validBusinessSettings(array $overrides = []): array
{
    return [
        'timezone' => 'Europe/Paris',
        'locale' => 'fr-FR',
        'currency' => 'eur',
        'distance_unit' => 'mi',
        ...$overrides,
    ];
}

it('keeps the page from guests, members and admins without the permission', function (): void {
    $this->get(route('admin.settings.business.edit'))->assertRedirectToRoute('login');
    $this->actingAs(User::factory()->create())->get(route('admin.settings.business.edit'))->assertForbidden();

    $admin = adminWithout('settings.update');
    $this->actingAs($admin)->get(route('admin.settings.business.edit'))->assertForbidden();
    $this->actingAs($admin)->put(route('admin.settings.business.update'), validBusinessSettings())->assertForbidden();
});

it('shows the current settings', function (): void {
    $this->actingAs(adminUser())
        ->get(route('admin.settings.business.edit'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('settings::business')
            ->where('settings', ['timezone' => 'UTC', 'locale' => 'en-US', 'currency' => 'USD', 'distance_unit' => 'km']));
});

it('saves the settings, shares them with every page and records the change', function (): void {
    $this->actingAs(adminUser())
        ->put(route('admin.settings.business.update'), validBusinessSettings())
        ->assertRedirectToRoute('admin.settings.business.edit')
        ->assertToast('Settings saved.');

    $settings = resolve(BusinessSettings::class)->refresh();

    expect($settings->timezone)->toBe('Europe/Paris')
        ->and($settings->locale)->toBe('fr-FR')
        ->and($settings->currency)->toBe('EUR')
        ->and($settings->distance_unit)->toBe(DistanceUnit::Mile)
        ->and(Activity::query()->where('description', 'business-updated')->sole()->getProperty('old'))
        ->toBe(['timezone' => 'UTC', 'locale' => 'en-US', 'currency' => 'USD', 'distance_unit' => 'km']);

    $this->get(route('notifications.index'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->where('business', ['timezone' => 'Europe/Paris', 'locale' => 'fr-FR', 'currency' => 'EUR', 'distance_unit' => 'mi']));
});

it('rejects settings the runtime cannot use', function (string $field, string $value): void {
    $this->actingAs(adminUser())
        ->put(route('admin.settings.business.update'), validBusinessSettings([$field => $value]))
        ->assertSessionHasErrors($field);

    expect(resolve(BusinessSettings::class)->refresh()->timezone)->toBe('UTC');
})->with([
    'unknown timezone' => ['timezone', 'Mars/Olympus'],
    'unknown regional format' => ['locale', 'xx-YY'],
    'underscored regional format' => ['locale', 'fr_FR'],
    'malformed currency' => ['currency', 'EURO'],
    'unknown currency' => ['currency', 'XYZ'],
    'unknown distance unit' => ['distance_unit', 'furlong'],
]);

it('validates the fields live', function (): void {
    $this->actingAs(adminUser())
        ->withHeaders(['Precognition' => 'true', 'Precognition-Validate-Only' => 'timezone'])
        ->putJson(route('admin.settings.business.update'), ['timezone' => 'Nowhere'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('timezone');
});
