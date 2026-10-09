<?php

declare(strict_types=1);

use App\Modules\Data\SessionData;

it('names the browser and platform from the user agent', function (?string $agent, ?string $browser, ?string $platform, bool $mobile): void {
    $session = SessionData::fromRecord($agent, '198.51.100.1', now()->getTimestamp(), isCurrent: true);

    expect($session->browser)->toBe($browser)
        ->and($session->platform)->toBe($platform)
        ->and($session->is_mobile)->toBe($mobile)
        ->and($session->ip_address)->toBe('198.51.100.1')
        ->and($session->is_current)->toBeTrue()
        ->and($session->last_active)->toBe(now()->toIso8601String());
})->with([
    'Edge on Windows' => ['Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0 Safari/537.36 Edg/129.0', 'Edge', 'Windows', false],
    'Firefox on Linux' => ['Mozilla/5.0 (X11; Linux x86_64; rv:131.0) Gecko/20100101 Firefox/131.0', 'Firefox', 'Linux', false],
    'Safari on iPad' => ['Mozilla/5.0 (iPad; CPU OS 18_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0 Mobile/15E148 Safari/604.1', 'Safari', 'iPadOS', true],
    'Chrome on Android' => ['Mozilla/5.0 (Linux; Android 15) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0 Safari/537.36', 'Chrome', 'Android', true],
    'Opera' => ['Mozilla/5.0 (Windows NT 10.0) AppleWebKit/537.36 Chrome/129.0 Safari/537.36 OPR/114.0', 'Opera', 'Windows', false],
    'a script' => ['curl/8.7.1', null, null, false],
    'no user agent' => [null, null, null, false],
]);
