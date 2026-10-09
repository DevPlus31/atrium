<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Collection;
use Inertia\Support\SessionKey;
use Inertia\Testing\AssertableInertia;
use Laravel\Sanctum\PersonalAccessToken;
use Spatie\Activitylog\Models\Activity;

beforeEach(function (): void {
    // The tokens screen sits behind password confirmation.
    $this->withSession(['auth.password_confirmed_at' => time()]);

    $this->artisan('admin:sync-permissions')->assertSuccessful();
});

it('requires a signed-in user', function (): void {
    $this->get(route('api-tokens.index'))->assertRedirectToRoute('login');
    $this->post(route('api-tokens.store'))->assertRedirectToRoute('login');
});

it("lists the user's tokens and the permissions a token may carry", function (): void {
    $admin = adminUser();
    $admin->createToken('Reporting', ['users.view'], now()->addDays(30));
    User::factory()->create()->createToken('Someone else');

    $this->actingAs($admin)->get(route('api-tokens.index'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('api::tokens')
            ->has('tokens', 1)
            ->where('tokens.0.name', 'Reporting')
            ->where('tokens.0.abilities', ['users.view'])
            ->where('tokens.0.last_used_at', null)
            ->where('tokens.0.expires_at', now()->addDays(30)->toIso8601String())
            ->where('abilities', fn (Collection $abilities): bool => $abilities->contains('users.view'))
            ->where('lifetimes', [30, 90, 365]));
});

it('offers members no permissions', function (): void {
    $this->actingAs(User::factory()->create())->get(route('api-tokens.index'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page->where('abilities', []));
});

it('creates a token and shows its secret once', function (): void {
    $admin = adminUser();

    $this->actingAs($admin)
        ->post(route('api-tokens.store'), ['name' => 'Reporting', 'expires_in_days' => 90, 'abilities' => ['users.view']])
        ->assertRedirectToRoute('api-tokens.index')
        ->assertInertiaFlash('apiToken');

    $token = PersonalAccessToken::query()->sole();
    $secret = session(SessionKey::FLASH_DATA)['apiToken'] ?? null;

    expect($token->tokenable_id)->toBe($admin->id)
        ->and($token->name)->toBe('Reporting')
        ->and($token->abilities)->toBe(['users.view'])
        ->and($token->expires_at?->toIso8601String())->toBe(now()->addDays(90)->toIso8601String())
        ->and($secret)->toBeString()
        ->and(PersonalAccessToken::findToken($secret)?->is($token))->toBeTrue()
        ->and(Activity::query()->where('event', 'api-token-created')->sole()->properties['attributes']['name'])->toBe('Reporting');
});

it('asks for the password before showing or creating tokens', function (): void {
    $this->flushSession();
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('api-tokens.index'))->assertRedirectToRoute('password.confirm');
    $this->actingAs($user)->post(route('api-tokens.store'), ['name' => 'x', 'expires_in_days' => 30])->assertRedirectToRoute('password.confirm');

    expect(PersonalAccessToken::query()->exists())->toBeFalse();
});

it('refuses permissions the user does not hold and odd lifetimes', function (array $payload, string $field): void {
    $this->actingAs(User::factory()->create())
        ->post(route('api-tokens.store'), ['name' => 'Sneaky', ...$payload])
        ->assertSessionHasErrors($field);

    expect(PersonalAccessToken::query()->exists())->toBeFalse();
})->with([
    'a permission the user lacks' => [['abilities' => ['users.delete']], 'abilities.0'],
    'not a permission' => [['abilities' => ['*']], 'abilities.0'],
    'a lifetime not offered' => [['expires_in_days' => 7], 'expires_in_days'],
    'no expiry' => [['expires_in_days' => null], 'expires_in_days'],
]);

it('requires a name', function (): void {
    $this->actingAs(User::factory()->create())
        ->post(route('api-tokens.store'), ['name' => ''])
        ->assertSessionHasErrors('name');
});

it("revokes one of the user's tokens", function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('Old')->accessToken;

    $this->actingAs($user)
        ->delete(route('api-tokens.destroy', $token->id))
        ->assertRedirectToRoute('api-tokens.index')
        ->assertToast('Token revoked.');

    expect(PersonalAccessToken::query()->exists())->toBeFalse()
        ->and(Activity::query()->where('event', 'api-token-revoked')->exists())->toBeTrue();
});

it("cannot revoke someone else's token", function (): void {
    $token = User::factory()->create()->createToken('Theirs')->accessToken;

    $this->actingAs(User::factory()->create())
        ->delete(route('api-tokens.destroy', $token->id))
        ->assertNotFound();

    expect($token->fresh())->not->toBeNull();
});

it('is a 404 for a malformed token id', function (): void {
    $this->actingAs(User::factory()->create())
        ->delete('/settings/api-tokens/not-a-number')
        ->assertNotFound();
});
