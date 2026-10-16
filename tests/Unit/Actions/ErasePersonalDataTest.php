<?php

declare(strict_types=1);

use App\Actions\ErasePersonalData;
use App\Models\User;
use App\Modules\PersonalDataRegistry;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;
use Spatie\Activitylog\Models\Activity;

/**
 * A user with a token, a notification and a session.
 */
function userLeavingTraces(): User
{
    $user = User::factory()->create();
    $user->createToken('Script', []);
    $user->notifications()->create(['id' => (string) Str::uuid(), 'type' => 'example', 'data' => []]);
    DB::table('sessions')->insert(['id' => (string) Str::uuid(), 'user_id' => $user->id, 'payload' => '', 'last_activity' => now()->getTimestamp()]);

    return $user;
}

it('erases the tokens, notifications and sessions an account leaves behind', function (): void {
    $user = userLeavingTraces();
    $other = userLeavingTraces();

    resolve(ErasePersonalData::class)($user);

    expect(PersonalAccessToken::query()->count())->toBe(1)
        ->and($other->tokens()->count())->toBe(1)
        ->and(DatabaseNotification::query()->count())->toBe(1)
        ->and($other->notifications()->count())->toBe(1)
        ->and(DB::table('sessions')->count())->toBe(1)
        ->and($other->browserSessions()->count())->toBe(1);
});

it('runs every module’s eraser and records the erasure', function (): void {
    $erased = [];
    resolve(PersonalDataRegistry::class)->add('test', 'test.things', fn (User $user): array => [], function (User $user) use (&$erased): void {
        $erased[] = $user->id;
    });
    $user = User::factory()->create();

    resolve(ErasePersonalData::class)($user);

    expect($erased)->toBe([$user->id])
        ->and(Activity::query()->where('event', 'personal-data-erased')->sole()->subject_id)->toBe($user->id);
});
