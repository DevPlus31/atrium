<?php

declare(strict_types=1);

use App\Models\User;
use Modules\Users\Actions\DeleteUser;
use Spatie\Activitylog\Models\Activity;

it('deletes the user', function (): void {
    $user = User::factory()->create();

    new DeleteUser()->handle($user);

    expect(User::query()->whereKey($user->id)->exists())->toBeFalse();
});

it('writes a deleted activity with the user attributes', function (): void {
    $user = User::factory()->create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);

    new DeleteUser()->handle($user);

    $activity = Activity::query()->where('event', 'deleted')->sole();

    expect($activity->log_name)->toBe('users')
        ->and($activity->getProperty('attributes'))->toBe([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ]);
});

it('rolls back the transaction when logging fails', function (): void {
    $user = User::factory()->create();

    User::deleted(function (): void {
        throw new RuntimeException('Activity log unavailable.');
    });

    expect(fn () => new DeleteUser()->handle($user))
        ->toThrow(RuntimeException::class);

    expect(User::query()->whereKey($user->id)->exists())->toBeTrue()
        ->and(Activity::query()->count())->toBe(0);
});
