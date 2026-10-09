<?php

declare(strict_types=1);

use App\Models\User;

it('lets a user pin their timezone and go back to automatic', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $this->actingAs($user);

    visit(route('appearance.edit'))
        ->assertSee('Language and region')
        ->click('#timezone')
        ->type('[cmdk-input]', 'Tokyo')
        ->click('Asia/Tokyo')
        ->assertSeeIn('#timezone', 'Asia/Tokyo')
        ->assertNoJavaScriptErrors();

    expect($user->refresh()->timezone)->toBe('Asia/Tokyo');

    visit(route('appearance.edit'))
        ->click('#timezone')
        ->click('[cmdk-item][data-value="automatic"]')
        ->assertSeeIn('#timezone', 'Automatic (this browser)');

    expect($user->refresh()->timezone)->toBeNull();
});
