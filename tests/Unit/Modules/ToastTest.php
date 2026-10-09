<?php

declare(strict_types=1);

use App\Modules\Toast;
use Inertia\Support\SessionKey;

it('flashes a toast of each type for the next page', function (string $type): void {
    Toast::{$type}('Saved.');

    expect(session(SessionKey::FLASH_DATA))->toBe(['toast' => ['type' => $type, 'message' => 'Saved.']]);
})->with(['success', 'info', 'warning', 'error']);
