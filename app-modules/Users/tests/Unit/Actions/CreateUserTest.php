<?php

declare(strict_types=1);

use App\Domain\Exceptions\InvalidEmailException;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Modules\Users\Actions\CreateUser;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Exceptions\RoleDoesNotExist;
use Spatie\Permission\Models\Role;

it('creates a user with a hashed password and synced roles', function (): void {
    Role::findOrCreate('editor');
    Event::fake([Registered::class]);

    $user = resolve(CreateUser::class)->handle(
        name: 'Jane Doe',
        email: 'jane@example.com',
        password: 'super-secret-password',
        roles: ['editor'],
    );

    expect($user->name)->toBe('Jane Doe')
        ->and($user->email)->toBe('jane@example.com')
        ->and(Hash::check('super-secret-password', $user->password))->toBeTrue()
        ->and($user->hasRole('editor'))->toBeTrue();

    Event::assertDispatched(Registered::class, fn (Registered $event): bool => $event->user->is($user));
});

it('creates an unverified user by default and a verified one on demand', function (): void {
    Role::findOrCreate('editor');

    $unverified = resolve(CreateUser::class)->handle(
        name: 'Jane Doe',
        email: 'jane@example.com',
        password: 'super-secret-password',
        roles: ['editor'],
    );

    $verified = resolve(CreateUser::class)->handle(
        name: 'John Doe',
        email: 'john@example.com',
        password: 'super-secret-password',
        roles: ['editor'],
        verified: true,
    );

    expect($unverified->hasVerifiedEmail())->toBeFalse()
        ->and($verified->hasVerifiedEmail())->toBeTrue();
});

it('writes a created activity with the given attributes', function (): void {
    Role::findOrCreate('editor');

    $user = resolve(CreateUser::class)->handle(
        name: 'Jane Doe',
        email: 'jane@example.com',
        password: 'super-secret-password',
        roles: ['editor'],
    );

    $activity = Activity::query()->where('event', 'created')->sole();

    expect($activity->log_name)->toBe('users')
        ->and($activity->subject_id)->toBe($user->id)
        ->and($activity->getProperty('attributes'))->toBe([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'roles' => ['editor'],
        ]);
});

it('rolls back the transaction when syncing roles fails', function (): void {
    expect(fn (): User => resolve(CreateUser::class)->handle(
        name: 'Jane Doe',
        email: 'jane@example.com',
        password: 'super-secret-password',
        roles: ['missing-role'],
    ))->toThrow(RoleDoesNotExist::class);

    expect(User::query()->count())->toBe(0)
        ->and(Activity::query()->count())->toBe(0);
});

it('rejects an invalid email before creating anything', function (): void {
    expect(fn (): User => resolve(CreateUser::class)->handle(
        name: 'Jane Doe',
        email: 'not-an-email',
        password: 'super-secret-password',
        roles: [],
    ))->toThrow(InvalidEmailException::class);

    expect(User::query()->count())->toBe(0);
});

it('announces the new account only once it is committed', function (): void {
    Event::fake([Registered::class]);

    try {
        DB::transaction(function (): void {
            resolve(CreateUser::class)->handle('Rolled Back', 'rolled@example.com', 'password', []);

            throw new RuntimeException('Something later failed.');
        });
    } catch (RuntimeException) {
        // The whole unit of work rolled back.
    }

    Event::assertNotDispatched(Registered::class);
    expect(User::query()->where('email', 'rolled@example.com')->exists())->toBeFalse();

    resolve(CreateUser::class)->handle('Kept', 'kept@example.com', 'password', []);

    Event::assertDispatched(Registered::class);
});
