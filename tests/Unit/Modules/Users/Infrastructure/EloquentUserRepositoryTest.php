<?php

declare(strict_types=1);

use App\Models\User;
use Modules\Users\Domain\Repositories\UserRepository;
use Modules\Users\Infrastructure\Repositories\EloquentUserRepository;

it('resolves to the eloquent implementation', function (): void {
    expect(resolve(UserRepository::class))->toBeInstanceOf(EloquentUserRepository::class);
});

it('persists a user', function (): void {
    $repository = resolve(UserRepository::class);

    $user = new User([
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'super-secret-password',
    ]);

    $repository->save($user);

    expect(User::query()->whereKey($user->id)->exists())->toBeTrue();
});
