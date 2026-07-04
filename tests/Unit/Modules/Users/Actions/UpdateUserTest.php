<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Modules\Users\Actions\UpdateUser;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Exceptions\RoleDoesNotExist;
use Spatie\Permission\Models\Role;

it('updates the user and syncs roles', function (): void {
    Role::findOrCreate('editor');
    $user = User::factory()->create(['name' => 'Old Name']);

    $updated = new UpdateUser()->handle(
        user: $user,
        name: 'New Name',
        email: $user->email,
        roles: ['editor'],
    );

    expect($updated->name)->toBe('New Name')
        ->and($updated->hasRole('editor'))->toBeTrue();
});

it('resets email verification and notifies when the email changes', function (): void {
    Notification::fake();
    $user = User::factory()->create(['email' => 'old@example.com']);

    $updated = new UpdateUser()->handle(
        user: $user,
        name: $user->name,
        email: 'new@example.com',
        roles: [],
    );

    expect($updated->email)->toBe('new@example.com')
        ->and($updated->email_verified_at)->toBeNull();

    Notification::assertSentTo($updated, VerifyEmail::class);
});

it('keeps email verification when the email stays the same', function (): void {
    Notification::fake();
    $user = User::factory()->create();

    $updated = new UpdateUser()->handle(
        user: $user,
        name: 'New Name',
        email: $user->email,
        roles: [],
    );

    expect($updated->email_verified_at)->not->toBeNull();

    Notification::assertNothingSent();
});

it('writes an updated activity with the old and new attributes', function (): void {
    Role::findOrCreate('editor');
    $user = User::factory()->create(['name' => 'Old Name', 'email' => 'old@example.com']);
    $user->assignRole('editor');

    new UpdateUser()->handle(
        user: $user,
        name: 'New Name',
        email: 'old@example.com',
        roles: [],
    );

    $activity = Activity::query()->where('event', 'updated')->sole();

    expect($activity->log_name)->toBe('users')
        ->and($activity->subject_id)->toBe($user->id)
        ->and($activity->getProperty('old'))->toBe([
            'name' => 'Old Name',
            'email' => 'old@example.com',
            'roles' => ['editor'],
        ])
        ->and($activity->getProperty('attributes'))->toBe([
            'name' => 'New Name',
            'email' => 'old@example.com',
            'roles' => [],
        ]);
});

it('rolls back the transaction when syncing roles fails', function (): void {
    $user = User::factory()->create(['name' => 'Old Name']);

    expect(fn (): User => new UpdateUser()->handle(
        user: $user,
        name: 'New Name',
        email: $user->email,
        roles: ['missing-role'],
    ))->toThrow(RoleDoesNotExist::class);

    expect($user->refresh()->name)->toBe('Old Name')
        ->and(Activity::query()->count())->toBe(0);
});
