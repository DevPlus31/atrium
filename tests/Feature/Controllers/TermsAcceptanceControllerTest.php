<?php

declare(strict_types=1);

use App\Models\User;
use Inertia\Testing\AssertableInertia;
use Lab404\Impersonate\Services\ImpersonateManager;
use Spatie\Activitylog\Models\Activity;

beforeEach(function (): void {
    $this->artisan('admin:sync-permissions')->assertSuccessful();
});

it('sends a user who has not accepted the current terms to accept them', function (): void {
    $user = User::factory()->withoutAcceptedTerms()->create();

    $this->actingAs($user)
        ->get(route('notifications.index'))
        ->assertRedirectToRoute('terms-acceptance.create');

    $this->get(route('terms-acceptance.create'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('terms-acceptance/create')
            ->where('firstTime', true));
});

it('says the terms changed to a user who accepted an older version', function (): void {
    $user = User::factory()->create(['terms_version' => '2000-01-01']);

    $this->actingAs($user)
        ->get(route('terms-acceptance.create'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page->where('firstTime', false));
});

it('records the acceptance and returns to where the user was going', function (): void {
    $user = User::factory()->withoutAcceptedTerms()->create();

    $this->actingAs($user)->get(route('notifications.index'));

    $this->post(route('terms-acceptance.store'), ['terms' => '1'])
        ->assertRedirect(route('notifications.index'))
        ->assertToast('Thank you. You’re all set.');

    expect($user->refresh()->hasAcceptedCurrentTerms())->toBeTrue()
        ->and(Activity::query()->where('event', 'terms-accepted')->sole()->causer_id)->toBe($user->id);

    $this->get(route('notifications.index'))->assertOk();
});

it('goes to the dashboard after accepting without a destination', function (): void {
    $this->actingAs(User::factory()->withoutAcceptedTerms()->create())
        ->post(route('terms-acceptance.store'), ['terms' => '1'])
        ->assertRedirectToRoute('dashboard');
});

it('requires ticking the box', function (): void {
    $user = User::factory()->withoutAcceptedTerms()->create();

    $this->actingAs($user)
        ->post(route('terms-acceptance.store'))
        ->assertSessionHasErrors('terms');

    expect($user->refresh()->terms_version)->toBeNull();
});

it('sends a user who already accepted away from the acceptance page', function (): void {
    $this->actingAs(User::factory()->create())
        ->get(route('terms-acceptance.create'))
        ->assertRedirectToRoute('dashboard');
});

it('keeps the privacy rights, the legal pages and signing out open before acceptance', function (string $method, string $route, array $parameters = []): void {
    $user = User::factory()->withoutAcceptedTerms()->create();

    $response = $this->actingAs($user)->{$method}(route($route, $parameters));

    expect($response->headers->get('Location'))->not->toBe(route('terms-acceptance.create'));
})->with([
    ['get', 'privacy.edit'],
    ['get', 'legal.show', ['document' => 'terms']],
    ['get', 'verification.notice'],
    ['post', 'logout'],
]);

it('stops only page visits: forms and JSON requests carry on', function (): void {
    $user = User::factory()->withoutAcceptedTerms()->create();

    $this->actingAs($user)
        ->patch(route('notifications.read-all'))
        ->assertRedirect();

    $this->getJson(route('notifications.index'))
        ->assertOk();
});

it('never asks an admin acting as the user to accept for them', function (): void {
    $admin = adminUser();
    $target = User::factory()->withoutAcceptedTerms()->create();

    $this->actingAs($admin);
    resolve(ImpersonateManager::class)->take($admin, $target);

    $this->get(route('notifications.index'))->assertOk();
});

it('asks nobody while acceptance is switched off', function (): void {
    config(['privacy.terms_version' => null]);

    $this->actingAs(User::factory()->withoutAcceptedTerms()->create())
        ->get(route('notifications.index'))
        ->assertOk();
});

it('leaves guests alone', function (): void {
    $this->get(route('login'))->assertOk();
});
