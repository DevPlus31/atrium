<?php

declare(strict_types=1);

use App\Models\User;
use App\Settings\GeneralSettings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use Spatie\Activitylog\Models\Activity;

beforeEach(function (): void {
    Storage::fake('public');
    $this->artisan('admin:sync-permissions')->assertSuccessful();
});

it('keeps the page from guests, members and admins without the permission', function (): void {
    $this->get(route('admin.settings.general.edit'))->assertRedirectToRoute('login');
    $this->actingAs(User::factory()->create())->get(route('admin.settings.general.edit'))->assertForbidden();

    $admin = adminWithout('settings.update');
    $this->actingAs($admin)->get(route('admin.settings.general.edit'))->assertForbidden();
    $this->actingAs($admin)->put(route('admin.settings.general.update'), ['registration_open' => false])->assertForbidden();
    $this->actingAs($admin)->delete(route('admin.settings.logo.destroy'))->assertForbidden();
});

it('shows the current settings', function (): void {
    $settings = resolve(GeneralSettings::class);
    $settings->support_email = 'help@example.com';
    $settings->save();

    $this->actingAs(adminUser())
        ->get(route('admin.settings.general.edit'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('settings::general')
            ->where('settings', ['logo' => null, 'support_email' => 'help@example.com', 'registration_open' => true]));
});

it('saves the settings and records the change', function (): void {
    $this->actingAs(adminUser())
        ->put(route('admin.settings.general.update'), ['support_email' => 'Help@Example.com', 'registration_open' => false])
        ->assertSessionHasErrors('support_email');

    $this->actingAs(adminUser())
        ->put(route('admin.settings.general.update'), ['support_email' => 'help@example.com', 'registration_open' => false])
        ->assertRedirectToRoute('admin.settings.general.edit')
        ->assertToast('Settings saved.');

    $settings = resolve(GeneralSettings::class)->refresh();

    expect($settings->support_email)->toBe('help@example.com')
        ->and($settings->registration_open)->toBeFalse()
        ->and(Activity::query()->where('description', 'general-updated')->sole()->getProperty('old'))
        ->toBe(['support_email' => null, 'registration_open' => true]);
});

it('clears the support email when left empty', function (): void {
    $settings = resolve(GeneralSettings::class);
    $settings->support_email = 'help@example.com';
    $settings->save();

    $this->actingAs(adminUser())
        ->put(route('admin.settings.general.update'), ['support_email' => '', 'registration_open' => true])
        ->assertSessionHasNoErrors();

    expect(resolve(GeneralSettings::class)->refresh()->support_email)->toBeNull();
});

it('validates the settings', function (array $payload, string $field): void {
    $this->actingAs(adminUser())
        ->put(route('admin.settings.general.update'), $payload)
        ->assertSessionHasErrors($field);
})->with([
    'invalid email' => [['support_email' => 'not-an-email', 'registration_open' => true], 'support_email'],
    'missing switch' => [['support_email' => ''], 'registration_open'],
    'not a boolean' => [['registration_open' => 'maybe'], 'registration_open'],
]);

it('uploads a logo, replaces it and removes it', function (): void {
    $admin = adminUser();

    $this->actingAs($admin)
        ->post(route('admin.settings.logo.update'), ['logo' => UploadedFile::fake()->image('one.png', 120, 40)])
        ->assertRedirectToRoute('admin.settings.general.edit')
        ->assertToast('Logo updated.');

    $first = resolve(GeneralSettings::class)->refresh()->logo_path;

    expect($first)->toStartWith('branding/');
    Storage::disk('public')->assertExists((string) $first);

    $this->actingAs($admin)->get(route('admin.settings.general.edit'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page->where('logo', Storage::disk('public')->url((string) $first)));

    $this->actingAs($admin)->post(route('admin.settings.logo.update'), ['logo' => UploadedFile::fake()->image('two.png', 120, 40)]);
    $second = resolve(GeneralSettings::class)->refresh()->logo_path;

    Storage::disk('public')->assertMissing((string) $first);
    Storage::disk('public')->assertExists((string) $second);

    $this->actingAs($admin)
        ->delete(route('admin.settings.logo.destroy'))
        ->assertToast('Logo removed.');

    Storage::disk('public')->assertMissing((string) $second);

    expect(resolve(GeneralSettings::class)->refresh()->logo_path)->toBeNull()
        ->and(Activity::query()->where('description', 'logo-removed')->exists())->toBeTrue();
});

it('records nothing when there is no logo to remove', function (): void {
    $this->actingAs(adminUser())->delete(route('admin.settings.logo.destroy'));

    expect(Activity::query()->where('description', 'logo-removed')->exists())->toBeFalse();
});

it('rejects logos that could carry script or are not usable images', function (UploadedFile $file): void {
    $this->actingAs(adminUser())
        ->post(route('admin.settings.logo.update'), ['logo' => $file])
        ->assertSessionHasErrors('logo');

    expect(resolve(GeneralSettings::class)->refresh()->logo_path)->toBeNull();
})->with([
    'svg' => fn (): UploadedFile => UploadedFile::fake()->createWithContent('logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'),
    'too small' => fn (): UploadedFile => UploadedFile::fake()->image('tiny.png', 16, 16),
    'too large' => fn (): UploadedFile => UploadedFile::fake()->image('huge.png', 200, 200)->size(3000),
]);

it('adds General to the Settings menu group for permitted admins', function (): void {
    $this->actingAs(adminUser())
        ->get(route('admin.settings.general.edit'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->where('nav', fn (Collection $nav): bool => collect($nav)->contains(fn (array $item): bool => $item['label'] === 'General' && $item['group'] === 'Settings')));
});
