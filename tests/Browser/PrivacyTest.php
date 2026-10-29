<?php

declare(strict_types=1);

use App\Models\ConsentRecord;
use App\Models\User;
use Pest\Browser\Execution;

beforeEach(function (): void {
    config(['privacy.consent.categories' => ['analytics', 'marketing']]);
});

it('asks a new visitor for cookie consent and remembers a refusal', function (): void {
    $page = visit(route('login'))
        ->assertPresent('[data-test="consent-banner"]')
        ->click('[data-test="consent-reject-all"]')
        ->assertNotPresent('[data-test="consent-banner"]');

    // The banner hides optimistically: wait for the choice to be stored (and
    // its cookie set) before reloading, or the reload can beat the request.
    Execution::instance()->waitForExpectation(function (): void {
        expect(ConsentRecord::query()->exists())->toBeTrue();
    });

    $page->navigate(route('login'))
        ->assertNotPresent('[data-test="consent-banner"]')
        ->assertNoJavaScriptErrors();

    expect(ConsentRecord::query()->sole()->granted->all())->toBe([]);
});

it('lets a visitor choose category by category', function (): void {
    visit(route('login'))
        ->click('[data-test="consent-customize"]')
        ->click('#consent-analytics')
        ->click('[data-test="save-consent"]')
        ->assertNotPresent('[data-test="consent-banner"]')
        ->assertNoJavaScriptErrors();

    Execution::instance()->waitForExpectation(function (): void {
        expect(ConsentRecord::query()->exists())->toBeTrue();
    });

    expect(ConsentRecord::query()->sole()->granted->pluck('value')->all())->toBe(['analytics']);
});

it('shows the legal pages with the cookie choices', function (): void {
    visit(route('legal.show', 'cookies'))
        ->assertSee('Cookie Policy')
        ->assertPresent('#choices [data-test="consent-form"]')
        ->assertNoJavaScriptErrors();
});

it('asks a user to accept new terms before going on', function (): void {
    $this->actingAs(User::factory()->withoutAcceptedTerms()->create());

    visit(route('privacy.edit'))
        ->assertSee('Download my data')
        ->navigate(route('notifications.index'))
        ->assertPathIs('/terms/accept')
        ->click('#terms')
        ->click('[data-test="accept-terms-button"]')
        ->assertPathIs('/notifications')
        ->assertNoJavaScriptErrors();
});
