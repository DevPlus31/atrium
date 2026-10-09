<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('may delete user account', function (): void {
    $user = User::factory()->create([
        'password' => Hash::make('password'),
    ]);

    $response = $this->actingAs($user)
        ->fromRoute('user-profile.edit')
        ->delete(route('user.destroy'), [
            'password' => 'password',
        ]);

    $response->assertRedirectToRoute('home');

    expect($user->fresh())->toBeNull();

    $this->assertGuest();
});

it('requires password to delete account', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->fromRoute('user-profile.edit')
        ->delete(route('user.destroy'), []);

    $response->assertRedirectToRoute('user-profile.edit')
        ->assertSessionHasErrors('password');

    expect($user->fresh())->not->toBeNull();
});

it('requires correct password to delete account', function (): void {
    $user = User::factory()->create([
        'password' => Hash::make('password'),
    ]);

    $response = $this->actingAs($user)
        ->fromRoute('user-profile.edit')
        ->delete(route('user.destroy'), [
            'password' => 'wrong-password',
        ]);

    $response->assertRedirectToRoute('user-profile.edit')
        ->assertSessionHasErrors('password');

    expect($user->fresh())->not->toBeNull();
});

it('requires a signed-in user', function (): void {
    $this->delete(route('user.destroy'))->assertRedirectToRoute('login');
});

it('keeps the only administrator from deleting their account', function (): void {
    $this->artisan('admin:sync-permissions')->assertSuccessful();
    $admin = adminUser();

    $this->actingAs($admin)
        ->fromRoute('user-profile.edit')
        ->delete(route('user.destroy'), ['password' => 'password'])
        ->assertSessionHasErrors(['password' => 'You are the only administrator. Give someone else the role before deleting your account.']);

    expect($admin->fresh())->not->toBeNull();
    $this->assertAuthenticatedAs($admin);
});

it('lets an administrator go once another one exists', function (): void {
    $this->artisan('admin:sync-permissions')->assertSuccessful();
    $admin = adminUser();
    adminUser();

    $this->actingAs($admin)
        ->delete(route('user.destroy'), ['password' => 'password'])
        ->assertRedirectToRoute('home');

    expect($admin->fresh())->toBeNull();
});
