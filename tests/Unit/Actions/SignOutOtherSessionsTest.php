<?php

declare(strict_types=1);

use App\Actions\SignOutOtherSessions;
use App\Models\User;
use Illuminate\Support\Facades\DB;

it('keeps the database rows alone when sessions live elsewhere', function (): void {
    $user = User::factory()->create();
    DB::table('sessions')->insert([
        'id' => 'elsewhere', 'user_id' => $user->id, 'payload' => '', 'last_activity' => now()->getTimestamp(),
    ]);
    $this->actingAs($user);
    $oldHash = $user->password;

    resolve(SignOutOtherSessions::class)->handle($user, 'password', 'current');

    expect(DB::table('sessions')->count())->toBe(1)
        ->and($user->refresh()->password)->not->toBe($oldHash);
});

it('revokes the API tokens too', function (): void {
    $user = User::factory()->create();
    $user->createToken('Script', []);
    $this->actingAs($user);

    resolve(SignOutOtherSessions::class)->handle($user, 'password', 'current');

    expect($user->tokens()->count())->toBe(0);
});
