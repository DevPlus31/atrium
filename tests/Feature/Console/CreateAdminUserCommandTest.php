<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Notification;

beforeEach(function (): void {
    Notification::fake();
});

it('creates a verified admin user from options', function (): void {
    $this->artisan('admin:create-user', [
        '--name' => 'First Admin',
        '--email' => 'admin@example.com',
        '--password' => 'super-secret-password',
    ])->assertSuccessful();

    $user = User::query()->where('email', 'admin@example.com')->sole();

    expect($user->name)->toBe('First Admin')
        ->and($user->hasRole('admin'))->toBeTrue()
        ->and($user->hasVerifiedEmail())->toBeTrue()
        ->and($user->can('users.view'))->toBeTrue()
        ->and($user->can('roles.view'))->toBeTrue();
});

it('prompts for missing input', function (): void {
    $this->artisan('admin:create-user')
        ->expectsQuestion('Name', 'Prompted Admin')
        ->expectsQuestion('Email address', 'prompted@example.com')
        ->expectsQuestion('Password', 'super-secret-password')
        ->assertSuccessful();

    $user = User::query()->where('email', 'prompted@example.com')->sole();

    expect($user->name)->toBe('Prompted Admin')
        ->and($user->hasRole('admin'))->toBeTrue();
});

it('rejects a duplicate email without creating a user', function (): void {
    User::factory()->create(['email' => 'taken@example.com']);

    $this->artisan('admin:create-user', [
        '--name' => 'First Admin',
        '--email' => 'taken@example.com',
        '--password' => 'super-secret-password',
    ])->assertFailed();

    expect(User::query()->count())->toBe(1);
});

it('rejects an invalid password', function (): void {
    $this->artisan('admin:create-user', [
        '--name' => 'First Admin',
        '--email' => 'admin@example.com',
        '--password' => 'short',
    ])->assertFailed();

    expect(User::query()->count())->toBe(0);
});
