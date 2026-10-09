<?php

declare(strict_types=1);

use App\Models\User;

it("returns the token's user", function (): void {
    $user = User::factory()->create(['name' => 'Ada', 'email' => 'ada@example.com', 'timezone' => 'Europe/Paris']);
    $token = $user->createToken('Script')->plainTextToken;

    $this->withToken($token)
        ->getJson(route('api.v1.user'))
        ->assertOk()
        ->assertExactJson(['data' => [
            'id' => $user->id,
            'name' => 'Ada',
            'email' => 'ada@example.com',
            'email_verified_at' => $user->email_verified_at?->toIso8601String(),
            'locale' => 'en',
            'timezone' => 'Europe/Paris',
            'created_at' => $user->created_at->toIso8601String(),
        ]]);

    expect($user->tokens()->sole()->last_used_at)->not->toBeNull();
});

it('rejects requests without a valid token', function (?string $token): void {
    $request = $token === null ? $this : $this->withToken($token);

    $request->getJson(route('api.v1.user'))->assertUnauthorized();
})->with([
    'no token' => [null],
    'unknown token' => ['1|not-a-real-token'],
    'expired token' => fn (): string => User::factory()->create()->createToken('Old', expiresAt: now()->subMinute())->plainTextToken,
]);

it('rejects a revoked token', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('Script');
    $token->accessToken->delete();

    $this->withToken($token->plainTextToken)->getJson(route('api.v1.user'))->assertUnauthorized();
});

it('does not accept the web session', function (): void {
    $this->actingAs(User::factory()->create(), 'web')
        ->getJson(route('api.v1.user'))
        ->assertUnauthorized();
});

it('is rate limited per user', function (): void {
    $token = User::factory()->create()->createToken('Script')->plainTextToken;

    $this->withToken($token)
        ->getJson(route('api.v1.user'))
        ->assertHeader('X-RateLimit-Limit', '60');
});
