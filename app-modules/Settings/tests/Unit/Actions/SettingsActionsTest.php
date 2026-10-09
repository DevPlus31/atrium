<?php

declare(strict_types=1);

use App\Enums\AnnouncementLevel;
use App\Settings\AnnouncementSettings;
use App\Settings\GeneralSettings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Modules\Settings\Actions\RemoveAnnouncement;
use Modules\Settings\Actions\RemoveLogo;
use Modules\Settings\Actions\UpdateAnnouncement;
use Modules\Settings\Actions\UpdateGeneralSettings;
use Modules\Settings\Actions\UpdateLogo;
use Spatie\Activitylog\Models\Activity;
use Spatie\LaravelSettings\Events\SavingSettings;

beforeEach(function (): void {
    Storage::fake('public');
});

it('saves the general settings and records the change', function (): void {
    $settings = resolve(GeneralSettings::class);

    resolve(UpdateGeneralSettings::class)->handle($settings, 'help@example.com', false);

    $fresh = resolve(GeneralSettings::class)->refresh();

    expect($fresh->support_email)->toBe('help@example.com')
        ->and($fresh->registration_open)->toBeFalse()
        ->and(Activity::query()->where('description', 'general-updated')->sole()->properties['attributes'])
        ->toBe(['support_email' => 'help@example.com', 'registration_open' => false]);
});

it('stores a new logo and deletes the previous file', function (): void {
    $settings = resolve(GeneralSettings::class);
    $action = resolve(UpdateLogo::class);

    $action->handle($settings, UploadedFile::fake()->image('one.png', 200, 200));

    $first = $settings->logo_path;
    $action->handle($settings, UploadedFile::fake()->image('two.png', 200, 200));

    expect($first)->not->toBeNull()
        ->and(Storage::disk('public')->exists((string) $first))->toBeFalse()
        ->and(Storage::disk('public')->exists((string) $settings->logo_path))->toBeTrue()
        ->and(Activity::query()->where('description', 'logo-updated')->count())->toBe(2);
});

it('removes the logo, and does nothing without one', function (): void {
    $settings = resolve(GeneralSettings::class);
    resolve(UpdateLogo::class)->handle($settings, UploadedFile::fake()->image('one.png', 200, 200));
    $path = (string) $settings->logo_path;
    $action = resolve(RemoveLogo::class);

    $action->handle($settings);
    $action->handle($settings);

    expect($settings->logo_path)->toBeNull()
        ->and(Storage::disk('public')->exists($path))->toBeFalse()
        ->and(Activity::query()->where('description', 'logo-removed')->count())->toBe(1);
});

it('publishes and removes the announcement', function (): void {
    $settings = resolve(AnnouncementSettings::class);

    resolve(UpdateAnnouncement::class)->handle($settings, 'Hello', AnnouncementLevel::Warning, now()->addDay());

    expect($settings->refresh()->message)->toBe('Hello')
        ->and($settings->level)->toBe(AnnouncementLevel::Warning)
        ->and($settings->ends_at)->toBe(now()->addDay()->toIso8601String())
        ->and($settings->isActive())->toBeTrue();

    $remove = resolve(RemoveAnnouncement::class);
    $remove->handle($settings);
    $remove->handle($settings);

    expect($settings->refresh()->message)->toBeNull()
        ->and($settings->isActive())->toBeFalse()
        ->and(Activity::query()->where('description', 'announcement-removed')->count())->toBe(1);
});

it('keeps the logo file when the settings cannot be saved', function (): void {
    $settings = resolve(GeneralSettings::class);
    resolve(UpdateLogo::class)->handle($settings, UploadedFile::fake()->image('one.png', 200, 200));
    $path = (string) $settings->logo_path;
    Event::listen(SavingSettings::class, function (): never {
        throw new RuntimeException('Settings store unavailable.');
    });

    expect(fn () => resolve(RemoveLogo::class)->handle($settings))->toThrow(RuntimeException::class);

    expect(Storage::disk('public')->exists($path))->toBeTrue();
});
