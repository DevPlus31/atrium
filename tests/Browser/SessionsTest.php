<?php

declare(strict_types=1);

use App\Models\User;

it('signs out the other sessions after the password is confirmed', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $this->actingAs($user);
    $oldHash = $user->password;

    visit(route('sessions.index'))
        ->assertSee('Browser sessions')
        ->click('[data-test="sign-out-other-sessions"]')
        ->type('#session_password', 'password')
        ->click('[data-test="confirm-sign-out-other-sessions"]')
        ->assertSee('Signed out of your other sessions.')
        ->assertNoJavaScriptErrors();

    expect($user->refresh()->password)->not->toBe($oldHash);
});
