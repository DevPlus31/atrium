<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Modules\Users\Actions\CreateUser;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Exceptions\RoleDoesNotExist;
use Spatie\Permission\Models\Role;

it('creates a user with a hashed password and synced roles', function (): void {
    Role::findOrCreate('editor');
    Event::fake([Registered::class]);

    $user = new CreateUser()->handle(
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

it('writes a created activity with the given attributes', function (): void {
    Role::findOrCreate('editor');

    $user = new CreateUser()->handle(
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
    expect(fn (): User => new CreateUser()->handle(
        name: 'Jane Doe',
        email: 'jane@example.com',
        password: 'super-secret-password',
        roles: ['missing-role'],
    ))->toThrow(RoleDoesNotExist::class);

    expect(User::query()->count())->toBe(0)
        ->and(Activity::query()->count())->toBe(0);
});
