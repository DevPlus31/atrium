<?php

declare(strict_types=1);

use App\Enums\ConsentCategory;
use App\Modules\ConsentCookie;
use Illuminate\Http\Request;

function requestWithConsentCookie(mixed $value): Request
{
    return Request::create('/', cookies: $value === null ? [] : [ConsentCookie::NAME => $value]);
}

it('reads the choice the cookie carries', function (): void {
    $cookie = ConsentCookie::fromRequest(requestWithConsentCookie(json_encode([
        'visitor' => 'a8b1c1d2-0000-4000-8000-000000000001',
        'version' => '2026-10-16',
        'granted' => ['analytics', 42],
    ])));

    expect($cookie)->toBeInstanceOf(ConsentCookie::class)
        ->and($cookie?->visitorId)->toBe('a8b1c1d2-0000-4000-8000-000000000001')
        ->and($cookie?->version)->toBe('2026-10-16')
        ->and($cookie?->granted)->toBe(['analytics'])
        ->and($cookie?->allows(ConsentCategory::Analytics))->toBeTrue()
        ->and($cookie?->allows(ConsentCategory::Marketing))->toBeFalse();
});

it('ignores a missing or unreadable cookie', function (mixed $value): void {
    expect(ConsentCookie::fromRequest(requestWithConsentCookie($value)))->toBeNull();
})->with([
    'missing' => [null],
    'not json' => ['yes'],
    'no visitor' => [json_encode(['version' => '1', 'granted' => []])],
    'no version' => [json_encode(['visitor' => 'v', 'granted' => []])],
    'granted not a list' => [json_encode(['visitor' => 'v', 'version' => '1', 'granted' => 'all'])],
]);

it('is current only for the configured cookie policy version', function (): void {
    config(['privacy.consent.version' => '2030-01-01']);

    expect(new ConsentCookie('v', '2030-01-01', [])->isCurrent())->toBeTrue()
        ->and(new ConsentCookie('v', '2029-01-01', [])->isCurrent())->toBeFalse();
});

it('becomes a lax cookie lasting the configured lifetime', function (): void {
    config(['privacy.consent.lifetime_days' => 30]);

    $cookie = new ConsentCookie('visitor-id', '2030-01-01', ['analytics'])->toCookie();

    expect($cookie->getName())->toBe(ConsentCookie::NAME)
        ->and(json_decode((string) $cookie->getValue(), true))->toBe(['visitor' => 'visitor-id', 'version' => '2030-01-01', 'granted' => ['analytics']])
        ->and($cookie->getExpiresTime())->toBe(now()->addDays(30)->getTimestamp())
        ->and($cookie->getSameSite())->toBe('lax')
        ->and($cookie->isHttpOnly())->toBeTrue();
});
