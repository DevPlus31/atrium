<?php

declare(strict_types=1);

use App\Enums\AnnouncementLevel;
use App\Models\User;
use App\Settings\AnnouncementSettings;
use Inertia\Testing\AssertableInertia;

function announce(string $message, ?string $endsAt = null): AnnouncementSettings
{
    $settings = resolve(AnnouncementSettings::class);
    $settings->message = $message;
    $settings->level = AnnouncementLevel::Info;
    $settings->ends_at = $endsAt;
    $settings->save();

    return $settings;
}

it('shares nothing while there is no announcement', function (): void {
    $this->get(route('login'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page->where('announcement', null));
});

it('shows the announcement to everyone, guests included', function (): void {
    $settings = announce('Maintenance tonight', now()->addHour()->toIso8601String());

    $this->get(route('login'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page->where('announcement', [
            'id' => $settings->version(),
            'message' => 'Maintenance tonight',
            'level' => 'info',
        ]));

    $this->actingAs(User::factory()->create())->get(route('user-profile.edit'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page->where('announcement.message', 'Maintenance tonight'));
});

it('stops showing it once it ends', function (): void {
    announce('Over', now()->subMinute()->toIso8601String());

    $this->get(route('login'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page->where('announcement', null));
});

it('hides it for a browser that dismissed it, until it changes', function (): void {
    // Settings are a singleton: keep the first version before it changes.
    $first = announce('First')->version();

    $this->from(route('login'))
        ->post(route('announcement.dismiss'))
        ->assertRedirect(route('login'))
        ->assertCookie(AnnouncementSettings::DISMISSED_COOKIE, $first);

    $this->withCookie(AnnouncementSettings::DISMISSED_COOKIE, $first)
        ->get(route('login'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page->where('announcement', null));

    $changed = announce('Second');

    $this->withCookie(AnnouncementSettings::DISMISSED_COOKIE, $first)
        ->get(route('login'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page->where('announcement.id', $changed->version()));
});
