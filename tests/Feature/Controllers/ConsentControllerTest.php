<?php

declare(strict_types=1);

use App\Enums\ConsentCategory;
use App\Models\ConsentRecord;
use App\Models\User;
use App\Modules\ConsentCookie;
use Inertia\Testing\AssertableInertia;

it('records a guest’s choice and remembers it in the browser', function (): void {
    $response = $this->fromRoute('login')
        ->post(route('consent.store'), ['granted' => ['analytics']])
        ->assertRedirectToRoute('login')
        ->assertToast('Your privacy choices are saved.')
        ->assertCookie(ConsentCookie::NAME);

    $record = ConsentRecord::query()->sole();
    $cookie = json_decode((string) $response->getCookie(ConsentCookie::NAME)?->getValue(), true);

    expect($record->user_id)->toBeNull()
        ->and($record->granted->all())->toBe([ConsentCategory::Analytics])
        ->and($record->gpc)->toBeFalse()
        ->and($cookie)->toBe(['visitor' => $record->visitor_id, 'version' => config('privacy.consent.version'), 'granted' => ['analytics']]);
});

it('links a signed-in user’s choice to their account and keeps the browser’s visitor ID', function (): void {
    $user = User::factory()->create();
    $visitor = 'a8b1c1d2-0000-4000-8000-000000000001';

    $this->actingAs($user)
        ->withCookie(ConsentCookie::NAME, (string) json_encode(['visitor' => $visitor, 'version' => 'old', 'granted' => []]))
        ->withHeader('Sec-GPC', '1')
        ->post(route('consent.store'), ['granted' => []])
        ->assertRedirect();

    $record = ConsentRecord::query()->sole();

    expect($record->user_id)->toBe($user->id)
        ->and($record->visitor_id)->toBe($visitor)
        ->and($record->granted->all())->toBe([])
        ->and($record->gpc)->toBeTrue();
});

it('shares the choice with the next page', function (): void {
    $this->withCookie(ConsentCookie::NAME, (string) json_encode([
        'visitor' => 'a8b1c1d2-0000-4000-8000-000000000001',
        'version' => config('privacy.consent.version'),
        'granted' => ['marketing'],
    ]))
        ->get(route('login'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->where('consent.decided', true)
            ->where('consent.granted', ['marketing'])
            ->where('consent.gpc', false)
            ->has('consent.categories', 2)
            ->where('consent.categories.1', [
                'value' => 'marketing',
                'label' => 'Marketing',
                'description' => 'Personalised advertising and sharing data with advertising partners.',
                'sale_or_sharing' => true,
            ]));
});

it('asks a new visitor', function (): void {
    $this->get(route('login'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->where('consent.decided', false)
            ->where('consent.granted', []));
});

it('rejects a malformed choice', function (array $data, string $error): void {
    $this->post(route('consent.store'), $data)->assertSessionHasErrors($error);

    expect(ConsentRecord::query()->exists())->toBeFalse();
})->with([
    'no choice' => [[], 'granted'],
    'not a list' => [['granted' => ['a' => 'analytics']], 'granted'],
    'unknown category' => [['granted' => ['tracking']], 'granted.0'],
    'twice' => [['granted' => ['analytics', 'analytics']], 'granted.0'],
]);

it('rejects a category the app does not offer', function (): void {
    config(['privacy.consent.categories' => ['analytics']]);

    $this->post(route('consent.store'), ['granted' => ['marketing']])->assertSessionHasErrors('granted.0');
});

it('throttles choices per client', function (): void {
    foreach (range(1, 30) as $attempt) {
        $this->post(route('consent.store'), ['granted' => []]);
    }

    $this->post(route('consent.store'), ['granted' => []])->assertTooManyRequests();
});
