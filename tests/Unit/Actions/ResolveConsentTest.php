<?php

declare(strict_types=1);

use App\Actions\ResolveConsent;
use App\Enums\ConsentCategory;
use App\Modules\ConsentCookie;
use App\Modules\Data\ConsentCategoryData;
use App\Modules\Data\ConsentData;
use Illuminate\Http\Request;

/**
 * @param  list<string>|null  $granted  null sends no cookie
 */
function consentRequest(?array $granted = null, ?string $version = null, bool $gpc = false): Request
{
    $cookies = $granted === null ? [] : [ConsentCookie::NAME => json_encode([
        'visitor' => 'a8b1c1d2-0000-4000-8000-000000000001',
        'version' => $version ?? config('privacy.consent.version'),
        'granted' => $granted,
    ])];

    return Request::create('/', cookies: $cookies, server: $gpc ? ['HTTP_SEC_GPC' => '1'] : []);
}

function resolveConsent(Request $request): ConsentData
{
    return resolve(ResolveConsent::class)($request);
}

it('allows nothing optional before the visitor chooses', function (): void {
    $consent = resolveConsent(consentRequest());

    expect($consent->decided)->toBeFalse()
        ->and($consent->granted)->toBe([])
        ->and($consent->gpc)->toBeFalse()
        ->and(array_map(fn (ConsentCategoryData $category): ConsentCategory => $category->value, $consent->categories))->toBe([ConsentCategory::Analytics, ConsentCategory::Marketing]);
});

it('allows what the visitor chose for the current cookie policy', function (): void {
    $consent = resolveConsent(consentRequest(['analytics']));

    expect($consent->decided)->toBeTrue()
        ->and($consent->granted)->toBe([ConsentCategory::Analytics])
        ->and($consent->allows(ConsentCategory::Analytics))->toBeTrue()
        ->and($consent->allows(ConsentCategory::Marketing))->toBeFalse();
});

it('asks again when the choice was made against an older cookie policy', function (): void {
    $consent = resolveConsent(consentRequest(['analytics', 'marketing'], version: '2000-01-01'));

    expect($consent->decided)->toBeFalse()
        ->and($consent->granted)->toBe([]);
});

it('ignores categories the app no longer offers', function (): void {
    config(['privacy.consent.categories' => ['analytics']]);

    expect(resolveConsent(consentRequest(['analytics', 'marketing']))->granted)->toBe([ConsentCategory::Analytics]);
});

it('treats a Global Privacy Control signal as an opt-out of selling and sharing', function (): void {
    $consent = resolveConsent(consentRequest(['analytics', 'marketing'], gpc: true));

    expect($consent->gpc)->toBeTrue()
        ->and($consent->granted)->toBe([ConsentCategory::Analytics]);
});

it('has nothing to ask when no optional category is offered', function (): void {
    config(['privacy.consent.categories' => []]);

    $consent = resolveConsent(consentRequest());

    expect($consent->decided)->toBeTrue()
        ->and($consent->categories)->toBe([])
        ->and($consent->granted)->toBe([]);
});
