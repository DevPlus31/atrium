<?php

declare(strict_types=1);

use App\Actions\RecordConsent;
use App\Enums\ConsentCategory;
use App\Models\ConsentRecord;
use App\Models\User;
use Illuminate\Support\Str;

it('records the choice as proof and returns the cookie for the browser', function (): void {
    $user = User::factory()->create();

    $cookie = resolve(RecordConsent::class)(
        visitorId: 'a8b1c1d2-0000-4000-8000-000000000001',
        user: $user,
        granted: [ConsentCategory::Analytics, ConsentCategory::Marketing],
        gpc: true,
    );

    $record = ConsentRecord::query()->sole();

    expect($cookie->visitorId)->toBe('a8b1c1d2-0000-4000-8000-000000000001')
        ->and($cookie->version)->toBe(config('privacy.consent.version'))
        ->and($cookie->granted)->toBe(['analytics', 'marketing'])
        ->and($record->visitor_id)->toBe($cookie->visitorId)
        ->and($record->user_id)->toBe($user->id)
        ->and($record->version)->toBe($cookie->version)
        ->and($record->granted->all())->toBe([ConsentCategory::Analytics, ConsentCategory::Marketing])
        ->and($record->gpc)->toBeTrue()
        ->and($record->created_at->toIso8601String())->toBe(now()->toIso8601String());
});

it('gives a new browser, or one with a malformed ID, a fresh visitor ID', function (?string $visitorId): void {
    $cookie = resolve(RecordConsent::class)(visitorId: $visitorId, user: null, granted: [], gpc: false);

    expect(Str::isUuid($cookie->visitorId))->toBeTrue()
        ->and($cookie->visitorId)->not->toBe($visitorId)
        ->and($cookie->granted)->toBe([])
        ->and(ConsentRecord::query()->sole()->user_id)->toBeNull();
})->with([
    'new browser' => [null],
    'malformed' => ['not-a-uuid'],
]);
