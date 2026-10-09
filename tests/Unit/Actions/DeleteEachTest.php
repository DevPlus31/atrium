<?php

declare(strict_types=1);

use App\Actions\DeleteEach;
use App\Models\User;

it('deletes every record through the given delete', function (): void {
    $users = User::factory()->count(2)->create();
    $kept = User::factory()->create();

    resolve(DeleteEach::class)->handle($users, function (User $user): void {
        $user->delete();
    });

    expect(User::query()->pluck('id')->all())->toBe([$kept->id]);
});

it('deletes none when one of them cannot be deleted', function (): void {
    $users = User::factory()->count(2)->create();

    expect(fn () => resolve(DeleteEach::class)->handle($users, function (User $user) use ($users): void {
        throw_if($user->is($users->last()), RuntimeException::class, 'Kept.');

        $user->delete();
    }))->toThrow(RuntimeException::class, 'Kept.');

    expect(User::query()->count())->toBe(2);
});
