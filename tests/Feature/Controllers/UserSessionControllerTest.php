<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia;
use Spatie\Activitylog\Models\Activity;

const CHROME_ON_MAC = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0 Safari/537.36';

function storeSession(User $user, string $id, int $minutesAgo, string $agent = CHROME_ON_MAC): void
{
    DB::table('sessions')->insert([
        'id' => $id,
        'user_id' => $user->id,
        'ip_address' => '203.0.113.7',
        'user_agent' => $agent,
        'payload' => '',
        'last_activity' => now()->subMinutes($minutesAgo)->getTimestamp(),
    ]);
}

it('requires a signed-in user', function (): void {
    $this->get(route('sessions.index'))->assertRedirectToRoute('login');
    $this->delete(route('sessions.destroy'))->assertRedirectToRoute('login');
});

it("lists the user's sessions, most recent first", function (): void {
    config()->set('session.driver', 'database');
    $user = User::factory()->create();
    storeSession($user, 'older', 60, 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) Mobile Safari/604.1');
    storeSession($user, 'newer', 5);
    storeSession(User::factory()->create(), 'someone-else', 1);

    $this->actingAs($user)->get(route('sessions.index'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('user-sessions/index')
            ->where('listable', true)
            ->has('sessions', 2)
            ->where('sessions.0', [
                'browser' => 'Chrome',
                'platform' => 'macOS',
                'is_mobile' => false,
                'ip_address' => '203.0.113.7',
                'is_current' => false,
                'last_active' => now()->subMinutes(5)->toIso8601String(),
            ])
            ->where('sessions.1.platform', 'iOS')
            ->where('sessions.1.is_mobile', true));
});

it('says when sessions cannot be listed', function (): void {
    $this->actingAs(User::factory()->create())->get(route('sessions.index'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->where('listable', false)
            ->where('sessions', []));
});

it('signs out the other sessions once the password is confirmed', function (): void {
    config()->set('session.driver', 'database');
    $user = User::factory()->create();
    $oldHash = $user->password;
    storeSession($user, 'laptop', 30);
    storeSession($user, 'phone', 10);
    $other = User::factory()->create();
    storeSession($other, 'theirs', 10);

    $this->actingAs($user)
        ->delete(route('sessions.destroy'), ['password' => 'password'])
        ->assertRedirectToRoute('sessions.index')
        ->assertToast('Signed out of your other sessions.');

    // Only the current session (saved after this request) is left for the user.
    expect(DB::table('sessions')->whereIn('id', ['laptop', 'phone'])->exists())->toBeFalse()
        ->and(DB::table('sessions')->where('id', 'theirs')->exists())->toBeTrue()
        ->and(DB::table('sessions')->where('user_id', $user->id)->count())->toBeLessThanOrEqual(1)
        ->and($user->refresh()->password)->not->toBe($oldHash)
        ->and(Hash::check('password', $user->password))->toBeTrue()
        ->and(Activity::query()->where('event', 'other-sessions-signed-out')->sole()->subject_id)->toBe($user->id);

    $this->assertAuthenticatedAs($user);
});

it('needs the right password', function (): void {
    $user = User::factory()->create();
    $oldHash = $user->password;

    $this->actingAs($user)
        ->delete(route('sessions.destroy'), ['password' => 'wrong-password'])
        ->assertSessionHasErrors('password');

    expect($user->refresh()->password)->toBe($oldHash);
});

it('signs a session out once the password changed elsewhere', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withSession(['password_hash_web' => hash_hmac('sha256', 'an old password hash', (string) config('app.key'))])
        ->get(route('user-profile.edit'))
        ->assertRedirectToRoute('login');

    $this->assertGuest();
});
