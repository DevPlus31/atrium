<?php

declare(strict_types=1);

use App\Settings\AnnouncementSettings;

beforeEach(function (): void {
    $this->artisan('admin:sync-permissions')->assertSuccessful();
});

it('publishes an announcement that everyone sees and can dismiss', function (): void {
    $this->actingAs(adminUser());

    visit(route('admin.settings.announcement.edit'))
        ->type('#message', 'Planned maintenance on Sunday.')
        ->click('[data-test="publish-announcement"]')
        ->assertSee('Announcement published.')
        ->assertSeeIn('[data-test="announcement"]', 'Planned maintenance on Sunday.')
        ->click('[data-test="announcement"] button')
        ->assertNotPresent('[data-test="announcement"]')
        ->assertNoJavaScriptErrors();
});

it('reads the end time in the admin’s own time zone', function (): void {
    $this->actingAs(adminUser(['timezone' => 'Asia/Tokyo']));

    visit(route('admin.settings.announcement.edit'))
        ->type('#message', 'Planned maintenance on Sunday.')
        ->type('#ends_at', '2030-01-01T09:00')
        ->click('[data-test="publish-announcement"]')
        ->assertSee('Announcement published.')
        ->assertNoJavaScriptErrors();

    expect(resolve(AnnouncementSettings::class)->refresh()->ends_at)->toBe('2030-01-01T00:00:00+00:00');
});
