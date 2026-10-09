<?php

declare(strict_types=1);

use App\Models\User;
use Inertia\Support\SessionKey;
use Spatie\Activitylog\Models\Activity;

it('renders profile edit page', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->fromRoute('dashboard')
        ->get(route('user-profile.edit'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('user-profile/edit')
            ->has('status'));
});

it('may update profile information', function (): void {
    $user = User::factory()->create([
        'name' => 'Old Name',
        'email' => 'old@example.com',
    ]);

    $response = $this->actingAs($user)
        ->fromRoute('user-profile.edit')
        ->patch(route('user-profile.update'), [
            'name' => 'New Name',
            'email' => 'new@example.com',
            'current_password' => 'password',
        ]);

    $response->assertRedirectToRoute('user-profile.edit')
        ->assertSessionHas(SessionKey::FLASH_DATA, [
            'toast' => [
                'type' => 'success',
                'message' => __('Profile updated.'),
            ],
        ]);

    expect($user->refresh()->name)->toBe('New Name')
        ->and($user->email)->toBe('new@example.com');
});

it('resets email verification when email changes', function (): void {
    $user = User::factory()->create([
        'email' => 'old@example.com',
        'email_verified_at' => now(),
    ]);

    $response = $this->actingAs($user)
        ->fromRoute('user-profile.edit')
        ->patch(route('user-profile.update'), [
            'name' => $user->name,
            'email' => 'new@example.com',
            'current_password' => 'password',
        ]);

    $response->assertRedirectToRoute('user-profile.edit');

    expect($user->refresh()->email_verified_at)->toBeNull();
});

it('keeps email verification when email stays the same', function (): void {
    $verifiedAt = now();

    $user = User::factory()->create([
        'email' => 'same@example.com',
        'email_verified_at' => $verifiedAt,
    ]);

    $response = $this->actingAs($user)
        ->fromRoute('user-profile.edit')
        ->patch(route('user-profile.update'), [
            'name' => 'New Name',
            'email' => 'same@example.com',
        ]);

    $response->assertRedirectToRoute('user-profile.edit');

    expect($user->refresh()->email_verified_at)->not->toBeNull();
});

it('requires name', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->fromRoute('user-profile.edit')
        ->patch(route('user-profile.update'), [
            'email' => 'test@example.com',
            'current_password' => 'password',
        ]);

    $response->assertRedirectToRoute('user-profile.edit')
        ->assertSessionHasErrors('name');
});

it('requires email', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->fromRoute('user-profile.edit')
        ->patch(route('user-profile.update'), [
            'name' => 'Test User',
        ]);

    $response->assertRedirectToRoute('user-profile.edit')
        ->assertSessionHasErrors('email');
});

it('requires valid email', function (string $email): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->fromRoute('user-profile.edit')
        ->patch(route('user-profile.update'), [
            'name' => 'Test User',
            'email' => $email,
            'current_password' => 'password',
        ]);

    $response->assertRedirectToRoute('user-profile.edit')
        ->assertSessionHasErrors('email');
})->with([
    'not an address' => 'not-an-email',
    // Laravel's `email` rule accepts it; registration's ValidEmail does not.
    'no top-level domain' => 'user@localhost',
]);

it('requires unique email except own', function (): void {
    $existingUser = User::factory()->create(['email' => 'existing@example.com']);
    $user = User::factory()->create(['email' => 'test@example.com']);

    $response = $this->actingAs($user)
        ->fromRoute('user-profile.edit')
        ->patch(route('user-profile.update'), [
            'name' => 'Test User',
            'email' => 'existing@example.com',
        ]);

    $response->assertRedirectToRoute('user-profile.edit')
        ->assertSessionHasErrors('email');
});

it('allows keeping same email', function (): void {
    $user = User::factory()->create([
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]);

    $response = $this->actingAs($user)
        ->fromRoute('user-profile.edit')
        ->patch(route('user-profile.update'), [
            'name' => 'Updated Name',
            'email' => 'test@example.com',
            'current_password' => 'password',
        ]);

    $response->assertRedirectToRoute('user-profile.edit')
        ->assertSessionDoesntHaveErrors();
});

it('records profile changes in the audit log', function (): void {
    $user = User::factory()->create(['name' => 'Old Name', 'email' => 'old@example.com']);

    $this->actingAs($user)->patch(route('user-profile.update'), [
        'name' => 'New Name',
        'email' => 'new@example.com',
        'current_password' => 'password',
    ])->assertSessionDoesntHaveErrors();

    $activity = Activity::query()->where('log_name', 'users')->where('event', 'updated')->sole();

    expect($activity->causer_id)->toBe($user->id)
        ->and($activity->subject_id)->toBe($user->id)
        ->and($activity->properties->get('old'))->toBe(['name' => 'Old Name', 'email' => 'old@example.com'])
        ->and($activity->properties->get('attributes'))->toBe(['name' => 'New Name', 'email' => 'new@example.com']);
});

it('redirects the settings root to the profile page for GET only', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/settings')->assertRedirect('/settings/profile');
    $this->actingAs($user)->post('/settings')->assertMethodNotAllowed();
});

it('requires the current password to change the email address', function (?string $password): void {
    $user = User::factory()->create(['email' => 'old@example.com']);

    $this->actingAs($user)
        ->fromRoute('user-profile.edit')
        ->patch(route('user-profile.update'), array_filter([
            'name' => $user->name,
            'email' => 'attacker@example.com',
            'current_password' => $password,
        ]))
        ->assertSessionHasErrors('current_password');

    expect($user->refresh()->email)->toBe('old@example.com');
})->with(['missing' => null, 'wrong' => 'not-the-password']);

it('changes the name alone without asking for the password', function (): void {
    $user = User::factory()->create(['email' => 'same@example.com']);

    $this->actingAs($user)
        ->patch(route('user-profile.update'), ['name' => 'Renamed', 'email' => 'same@example.com'])
        ->assertSessionHasNoErrors();

    expect($user->refresh()->name)->toBe('Renamed');
});

it('requires a signed-in user', function (): void {
    $this->get(route('user-profile.edit'))->assertRedirectToRoute('login');
    $this->patch(route('user-profile.update'))->assertRedirectToRoute('login');
});
