<?php

declare(strict_types=1);

use App\Models\User;

it('renders the passkeys settings page', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->session(['auth.password_confirmed_at' => time()]);

    $passkey = $user->passkeys()->create([
        'name' => 'Work laptop',
        'credential_id' => 'credential-1',
        'credential' => ['aaguid' => '00000000-0000-0000-0000-000000000000'],
    ]);
    $passkey->forceFill(['last_used_at' => now()])->save();

    visit(route('passkeys.show'))
        ->assertSee('Passkeys')
        ->assertSee('Add passkey')
        ->assertSee('Work laptop')
        ->assertNoJavaScriptErrors();
});
