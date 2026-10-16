<?php

declare(strict_types=1);

use App\Models\User;
use Inertia\Testing\AssertableInertia;

it('sends guests to sign in', function (): void {
    $this->get(route('privacy.edit'))->assertRedirectToRoute('login');
});

it('shows the privacy settings with when the terms were accepted', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('privacy.edit'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('user-privacy/edit')
            ->where('termsAcceptedAt', now()->toIso8601String())
            ->has('consent.categories', 2));
});

it('shows no acceptance date for an account that never accepted the terms', function (): void {
    config(['privacy.terms_version' => null]);

    $this->actingAs(User::factory()->withoutAcceptedTerms()->create())
        ->get(route('privacy.edit'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page->where('termsAcceptedAt', null));
});
