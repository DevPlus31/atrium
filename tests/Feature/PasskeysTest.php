<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Str;
use Tests\Support\FakeAuthenticator;
use Tests\TestCase;

it('registers a passkey', function (): void {
    $user = User::factory()->create();
    $authenticator = new FakeAuthenticator;

    $this->actingAs($user)->session(['auth.password_confirmed_at' => time()]);

    /** @var array{challenge: string, rp: array{id: string}, user: array{id: string}} $options */
    $options = $this->getJson(route('passkey.registration-options'))
        ->assertOk()
        ->json('options');

    $response = $this->postJson(route('passkey.store'), [
        'name' => 'Work laptop',
        'credential' => $authenticator->attest($options),
    ]);

    $response->assertOk()
        ->assertJson(['status' => 'passkey-registered', 'name' => 'Work laptop']);

    expect($user->passkeys()->count())->toBe(1)
        ->and($user->passkeys()->sole()->name)->toBe('Work laptop')
        ->and($user->passkeys()->sole()->credential_id)->toBe($authenticator->credentialId());
});

it('logs in with a passkey', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $authenticator = registerPasskeyFor($this, $user);

    $this->post(route('logout'));
    $this->assertGuest();

    /** @var array{challenge: string, rpId: string} $options */
    $options = $this->getJson(route('passkey.login-options'))
        ->assertOk()
        ->json('options');

    $previousSessionId = session()->getId();

    $response = $this->postJson(route('passkey.login'), [
        'credential' => $authenticator->assert($options),
    ]);

    $response->assertOk()->assertJsonStructure(['redirect']);

    $this->assertAuthenticatedAs($user);

    expect(session()->getId())->not->toBe($previousSessionId);
});

it('bypasses the two factor challenge when logging in with a passkey', function (): void {
    $user = User::factory()->create();

    expect($user->hasEnabledTwoFactorAuthentication())->toBeTrue();

    $authenticator = registerPasskeyFor($this, $user);

    $this->post(route('logout'));
    $this->assertGuest();

    /** @var array{challenge: string, rpId: string} $options */
    $options = $this->getJson(route('passkey.login-options'))
        ->assertOk()
        ->json('options');

    $response = $this->postJson(route('passkey.login'), [
        'credential' => $authenticator->assert($options),
    ]);

    $response->assertOk()
        ->assertJsonStructure(['redirect'])
        ->assertJsonMissingPath('two_factor');

    $this->assertAuthenticatedAs($user);
    $response->assertSessionMissing('login.id');
});

it('rejects an unknown passkey credential', function (): void {
    $authenticator = new FakeAuthenticator;

    /** @var array{challenge: string, rpId: string} $options */
    $options = $this->getJson(route('passkey.login-options'))
        ->assertOk()
        ->json('options');

    $response = $this->postJson(route('passkey.login'), [
        'credential' => $authenticator->assert($options),
    ]);

    $response->assertStatus(422)->assertJsonValidationErrors(['credential']);

    $this->assertGuest();
});

it('rejects a passkey registration when the options session expired', function (): void {
    $user = User::factory()->create();
    $authenticator = new FakeAuthenticator;

    $this->actingAs($user)->session(['auth.password_confirmed_at' => time()]);

    $response = $this->postJson(route('passkey.store'), [
        'name' => 'Work laptop',
        'credential' => $authenticator->attest([
            'challenge' => 'dGVzdC1jaGFsbGVuZ2U',
            'rp' => ['id' => 'localhost'],
            'user' => ['id' => 'dGVzdC1oYW5kbGU'],
        ]),
    ]);

    $response->assertStatus(422)->assertJsonValidationErrors(['credential']);

    expect($user->passkeys()->count())->toBe(0);
});

it('deletes a passkey', function (): void {
    $user = User::factory()->create();

    $passkey = $user->passkeys()->create([
        'name' => 'Work laptop',
        'credential_id' => 'credential-1',
        'credential' => ['aaguid' => '00000000-0000-0000-0000-000000000000'],
    ]);

    $this->actingAs($user)->session(['auth.password_confirmed_at' => time()]);

    $response = $this->deleteJson(route('passkey.destroy', ['passkey' => $passkey->id]));

    $response->assertOk()->assertJson(['status' => 'passkey-deleted']);

    $this->assertDatabaseMissing('passkeys', ['id' => $passkey->id]);
});

it('forbids deleting another user passkey', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $passkey = $otherUser->passkeys()->create([
        'name' => 'Someone else',
        'credential_id' => 'credential-2',
        'credential' => ['aaguid' => '00000000-0000-0000-0000-000000000000'],
    ]);

    $this->actingAs($user)->session(['auth.password_confirmed_at' => time()]);

    $response = $this->deleteJson(route('passkey.destroy', ['passkey' => $passkey->id]));

    $response->assertForbidden();

    $this->assertDatabaseHas('passkeys', ['id' => $passkey->id]);
});

it('rate limits passkey login options requests', function (): void {
    $this->withCredentials()
        ->withCookie((string) config('session.cookie'), Str::random(40));

    foreach (range(1, 10) as $attempt) {
        $this->getJson(route('passkey.login-options'))->assertOk();
    }

    $this->getJson(route('passkey.login-options'))->assertStatus(429);
});

/**
 * Register a passkey for the given user through the full attestation
 * ceremony and return the authenticator holding its private key.
 */
function registerPasskeyFor(TestCase $test, User $user): FakeAuthenticator
{
    $authenticator = new FakeAuthenticator;

    $test->actingAs($user)->session(['auth.password_confirmed_at' => time()]);

    /** @var array{challenge: string, rp: array{id: string}, user: array{id: string}} $options */
    $options = $test->getJson(route('passkey.registration-options'))
        ->assertOk()
        ->json('options');

    $test->postJson(route('passkey.store'), [
        'name' => 'Work laptop',
        'credential' => $authenticator->attest($options),
    ])->assertOk();

    return $authenticator;
}
