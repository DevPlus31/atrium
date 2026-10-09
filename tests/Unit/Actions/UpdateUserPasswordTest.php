<?php

declare(strict_types=1);

use App\Actions\UpdateUserPassword;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('may update a user password', function (): void {
    $user = User::factory()->create([
        'password' => Hash::make('old-password'),
    ]);

    $action = resolve(UpdateUserPassword::class);

    $action->handle($user, 'new-password');

    expect(Hash::check('new-password', $user->refresh()->password))->toBeTrue()
        ->and(Hash::check('old-password', $user->password))->toBeFalse();
});

it("revokes the user's API tokens", function (): void {
    $user = User::factory()->create();
    $user->createToken('Script', []);

    $other = User::factory()->create();
    $other->createToken('Theirs', []);

    resolve(UpdateUserPassword::class)->handle($user, 'new-password-123');

    expect($user->tokens()->count())->toBe(0)
        ->and($other->tokens()->count())->toBe(1);
});
