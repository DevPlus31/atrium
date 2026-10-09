<?php

declare(strict_types=1);

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
