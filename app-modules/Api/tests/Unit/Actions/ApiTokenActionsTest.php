<?php

declare(strict_types=1);

use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;
use Modules\Api\Actions\CreateApiToken;
use Modules\Api\Actions\RevokeApiToken;
use Spatie\Activitylog\Models\Activity;

it('issues a token with the given permissions and expiry', function (): void {
    $user = User::factory()->create();

    $token = resolve(CreateApiToken::class)->handle($user, 'Script', ['users.view'], now()->addDays(30));

    expect($token->accessToken->tokenable_id)->toBe($user->id)
        ->and($token->accessToken->abilities)->toBe(['users.view'])
        ->and($token->accessToken->expires_at?->toIso8601String())->toBe(now()->addDays(30)->toIso8601String())
        ->and(PersonalAccessToken::findToken($token->plainTextToken)?->is($token->accessToken))->toBeTrue()
        ->and(Activity::query()->where('event', 'api-token-created')->sole()->properties['attributes'])->toBe([
            'name' => 'Script',
            'abilities' => ['users.view'],
            'expires_at' => now()->addDays(30)->toIso8601String(),
        ]);
});

it('issues a token without permissions', function (): void {
    $token = resolve(CreateApiToken::class)->handle(User::factory()->create(), 'Profile', [], now()->addDays(30));

    expect($token->accessToken->abilities)->toBe([]);
});

it('revokes a token', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('Old', [])->accessToken;

    resolve(RevokeApiToken::class)->handle($user, $token);

    expect(PersonalAccessToken::query()->exists())->toBeFalse()
        ->and(Activity::query()->where('event', 'api-token-revoked')->sole()->properties['attributes'])->toBe(['name' => 'Old']);
});

it('keeps no token when the activity log cannot be written', function (): void {
    $user = User::factory()->create();
    Activity::creating(function (): never {
        throw new RuntimeException('Activity log unavailable.');
    });

    expect(fn () => resolve(CreateApiToken::class)->handle($user, 'Script', [], now()->addDays(30)))
        ->toThrow(RuntimeException::class);

    expect(PersonalAccessToken::query()->count())->toBe(0);
});

it('keeps the token when its revocation cannot be logged', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('Script', [], now()->addDays(30))->accessToken;
    Activity::creating(function (): never {
        throw new RuntimeException('Activity log unavailable.');
    });

    expect(fn () => resolve(RevokeApiToken::class)->handle($user, $token))
        ->toThrow(RuntimeException::class);

    expect(PersonalAccessToken::query()->whereKey($token->id)->exists())->toBeTrue();
});
