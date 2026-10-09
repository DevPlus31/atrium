<?php

declare(strict_types=1);

use App\Enums\AnnouncementLevel;
use App\Models\User;
use App\Settings\AnnouncementSettings;
use Inertia\Testing\AssertableInertia;
use Spatie\Activitylog\Models\Activity;

beforeEach(function (): void {
    $this->artisan('admin:sync-permissions')->assertSuccessful();
});

it('keeps the page from guests, members and admins without the permission', function (): void {
    $this->get(route('admin.settings.announcement.edit'))->assertRedirectToRoute('login');

    $this->actingAs(User::factory()->create())->get(route('admin.settings.announcement.edit'))->assertForbidden();

    $admin = adminWithout('settings.update');

    $this->actingAs($admin)->get(route('admin.settings.announcement.edit'))->assertForbidden();
    $this->actingAs($admin)->put(route('admin.settings.announcement.update'), ['message' => 'Hi', 'level' => 'info'])->assertForbidden();
    $this->actingAs($admin)->delete(route('admin.settings.announcement.destroy'))->assertForbidden();
});

it('shows the current announcement', function (): void {
    $settings = resolve(AnnouncementSettings::class);
    $settings->message = 'Maintenance tonight';
    $settings->level = AnnouncementLevel::Warning;
    $settings->save();

    $this->actingAs(adminUser())->get(route('admin.settings.announcement.edit'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('settings::announcement')
            ->where('settings', [
                'message' => 'Maintenance tonight',
                'level' => 'warning',
                'ends_at' => null,
                'is_active' => true,
            ])
            ->where('maxLength', 280));
});

it('publishes an announcement, storing its end in UTC', function (): void {
    $this->actingAs(adminUser())
        ->put(route('admin.settings.announcement.update'), [
            'message' => '  Maintenance on Sunday.  ',
            'level' => 'warning',
            'ends_at' => now()->addDay()->setTimezone('Europe/Paris')->toIso8601String(),
        ])
        ->assertRedirectToRoute('admin.settings.announcement.edit')
        ->assertToast('Announcement published.');

    $settings = resolve(AnnouncementSettings::class)->refresh();

    expect($settings->message)->toBe('Maintenance on Sunday.')
        ->and($settings->level)->toBe(AnnouncementLevel::Warning)
        ->and($settings->ends_at)->toBe(now()->addDay()->utc()->toIso8601String())
        ->and(Activity::query()->where('description', 'announcement-updated')->exists())->toBeTrue();
});

it('publishes an announcement without an end', function (): void {
    $this->actingAs(adminUser())
        ->put(route('admin.settings.announcement.update'), ['message' => 'Welcome', 'level' => 'info', 'ends_at' => null])
        ->assertSessionHasNoErrors();

    expect(resolve(AnnouncementSettings::class)->refresh()->ends_at)->toBeNull();
});

it('validates the announcement', function (array $payload, string $field): void {
    $this->actingAs(adminUser())
        ->put(route('admin.settings.announcement.update'), [...['message' => 'Hi', 'level' => 'info'], ...$payload])
        ->assertSessionHasErrors($field);

    expect(resolve(AnnouncementSettings::class)->refresh()->message)->toBeNull();
})->with([
    'no message' => [['message' => ''], 'message'],
    'too long' => [['message' => str_repeat('a', 281)], 'message'],
    'unknown level' => [['level' => 'panic'], 'level'],
    'ends in the past' => [['ends_at' => '2020-01-01T00:00:00Z'], 'ends_at'],
]);

it('removes the announcement', function (): void {
    $settings = resolve(AnnouncementSettings::class);
    $settings->message = 'Old news';
    $settings->save();

    $this->actingAs(adminUser())
        ->delete(route('admin.settings.announcement.destroy'))
        ->assertRedirectToRoute('admin.settings.announcement.edit')
        ->assertToast('Announcement removed.');

    expect(resolve(AnnouncementSettings::class)->refresh()->message)->toBeNull();
});
