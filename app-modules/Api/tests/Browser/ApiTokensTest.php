<?php

declare(strict_types=1);

use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

it('creates a token, shows it once and revokes it', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $this->actingAs($user);

    // The screen asks for the password first.
    visit(route('api-tokens.index'))
        ->assertPathIs('/user/confirm-password')
        ->type('#password', 'password')
        ->click('[data-test="confirm-password-button"]')
        ->assertPathIs('/settings/api-tokens')
        ->assertSee('You have no API tokens.')
        ->type('#token_name', 'Reporting script')
        ->click('[data-test="create-api-token"]')
        ->assertPresent('[data-test="new-api-token"]')
        ->assertSee("Copy your new token now. You won't be able to see it again.")
        ->assertSee('Reporting script')
        ->click('[data-test="api-token"] button:has-text("Revoke")')
        ->click('[role="alertdialog"] button:has-text("Revoke")')
        ->assertSee('Token revoked.')
        ->assertSee('You have no API tokens.')
        ->assertNotPresent('[data-test="new-api-token"]')
        ->assertNoJavaScriptErrors();

    expect(PersonalAccessToken::query()->exists())->toBeFalse();
});
