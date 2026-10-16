<?php

declare(strict_types=1);

use App\Actions\RegisterUser;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;

it('may create a user', function (): void {
    Event::fake([Registered::class]);

    $action = resolve(RegisterUser::class);

    $user = $action([
        'name' => 'Test User',
        'email' => 'example@email.com',
    ], 'password');

    expect($user)->toBeInstanceOf(User::class)
        ->and($user->name)->toBe('Test User')
        ->and($user->email)->toBe('example@email.com')
        ->and($user->password)->not->toBe('password')
        ->and($user->terms_version)->toBe(config('privacy.terms_version'))
        ->and($user->terms_accepted_at)->not->toBeNull();

    Event::assertDispatched(Registered::class);
});
