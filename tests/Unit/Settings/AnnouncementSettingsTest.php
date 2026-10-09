<?php

declare(strict_types=1);

use App\Enums\AnnouncementLevel;
use App\Settings\AnnouncementSettings;

it('is active while it has a message and has not ended', function (?string $message, ?string $endsAt, bool $active): void {
    $settings = resolve(AnnouncementSettings::class);
    $settings->message = $message;
    $settings->ends_at = $endsAt;

    expect($settings->isActive())->toBe($active);
})->with([
    'no message' => [null, null, false],
    'no end' => ['Hi', null, true],
    'ends later' => ['Hi', '2099-01-01T00:00:00+00:00', true],
    'ended' => ['Hi', '2000-01-01T00:00:00+00:00', false],
]);

it('gets a new version whenever it changes', function (): void {
    $settings = resolve(AnnouncementSettings::class);
    $settings->message = 'Hi';

    $first = $settings->version();

    $settings->level = AnnouncementLevel::Warning;

    expect($settings->version())->not->toBe($first)
        ->toHaveLength(16);
});
