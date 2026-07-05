<?php

declare(strict_types=1);

use App\Models\User;

it('renders passkeys page', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->session(['auth.password_confirmed_at' => time()]);

    $response = $this->fromRoute('dashboard')
        ->get(route('passkeys.show'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('user-passkeys/show')
            ->where('canManagePasskeys', true)
            ->has('passkeys', 0));
});

it('lists the user passkeys', function (): void {
    $user = User::factory()->create();

    $passkey = $user->passkeys()->create([
        'name' => 'Work laptop',
        'credential_id' => 'credential-1',
        'credential' => ['aaguid' => 'ea9b8d66-4d01-1d21-3ce4-b6b48cb575d4'],
    ]);
    $passkey->forceFill(['last_used_at' => now()])->save();

    $otherUser = User::factory()->create();
    $otherUser->passkeys()->create([
        'name' => 'Someone else',
        'credential_id' => 'credential-2',
        'credential' => ['aaguid' => '00000000-0000-0000-0000-000000000000'],
    ]);

    $this->actingAs($user)->session(['auth.password_confirmed_at' => time()]);

    $response = $this->fromRoute('dashboard')
        ->get(route('passkeys.show'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('user-passkeys/show')
            ->has('passkeys', 1)
            ->where('passkeys.0.id', $passkey->id)
            ->where('passkeys.0.name', 'Work laptop')
            ->where('passkeys.0.authenticator', 'Google Password Manager')
            ->where('passkeys.0.last_used_at', now()->toIso8601String())
            ->where('passkeys.0.created_at', now()->toIso8601String()));
});

it('redirects guests to the login page', function (): void {
    $response = $this->get(route('passkeys.show'));

    $response->assertRedirect(route('login'));
});

it('redirects to password confirmation when the password was not confirmed', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('passkeys.show'));

    $response->assertRedirect(route('password.confirm'));
});
