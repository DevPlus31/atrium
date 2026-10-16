<?php

declare(strict_types=1);

use App\Actions\PruneExpiredPersonalData;
use App\Models\ConsentRecord;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

function notificationReadAt(User $user, ?CarbonInterface $readAt): void
{
    $user->notifications()->create(['id' => (string) Str::uuid(), 'type' => 'example', 'data' => [], 'read_at' => $readAt]);
}

it('deletes notifications read longer ago than the retention period', function (): void {
    config(['privacy.retention.read_notifications_days' => 90]);
    $user = User::factory()->create();
    notificationReadAt($user, now()->subDays(91));
    notificationReadAt($user, now()->subDays(89));
    notificationReadAt($user, null);

    $pruned = resolve(PruneExpiredPersonalData::class)();

    expect($pruned['notifications'])->toBe(1)
        ->and(DatabaseNotification::query()->count())->toBe(2);
});

it('deletes consent records older than the retention period', function (): void {
    config(['privacy.retention.consent_records_days' => 730]);
    ConsentRecord::factory()->create(['created_at' => now()->subDays(731)]);
    $kept = ConsentRecord::factory()->create(['created_at' => now()->subDays(729)]);

    expect(resolve(PruneExpiredPersonalData::class)()['consent_records'])->toBe(1)
        ->and(ConsentRecord::query()->sole()->is($kept))->toBeTrue();
});

it('deletes old sign-ups that never verified their address nor got a role', function (): void {
    config(['privacy.retention.abandoned_signups_days' => 30]);
    $abandoned = User::factory()->unverified()->create(['created_at' => now()->subDays(31)]);
    $abandoned->createToken('Script', []);

    $recent = User::factory()->unverified()->create(['created_at' => now()->subDays(29)]);
    $verified = User::factory()->create(['created_at' => now()->subDays(31)]);
    $invited = User::factory()->unverified()->create(['created_at' => now()->subDays(31)]);
    $invited->assignRole(Role::findOrCreate('editor'));

    expect(resolve(PruneExpiredPersonalData::class)()['abandoned_signups'])->toBe(1)
        ->and(User::query()->pluck('id')->sort()->values()->all())->toBe(collect([$recent->id, $verified->id, $invited->id])->sort()->values()->all())
        ->and($abandoned->tokens()->count())->toBe(0);
});

it('keeps unverified sign-ups when their retention is switched off', function (): void {
    config(['privacy.retention.abandoned_signups_days' => null]);
    User::factory()->unverified()->create(['created_at' => now()->subYears(5)]);

    expect(resolve(PruneExpiredPersonalData::class)()['abandoned_signups'])->toBe(0)
        ->and(User::query()->count())->toBe(1);
});

it('records what it deleted, and stays silent when there was nothing', function (): void {
    expect(resolve(PruneExpiredPersonalData::class)())->toBe(['notifications' => 0, 'consent_records' => 0, 'abandoned_signups' => 0])
        ->and(Activity::query()->where('event', 'retention-pruned')->exists())->toBeFalse();

    ConsentRecord::factory()->create(['created_at' => now()->subYears(5)]);

    resolve(PruneExpiredPersonalData::class)();

    expect(Activity::query()->where('event', 'retention-pruned')->sole()->getProperty('attributes'))
        ->toBe(['notifications' => 0, 'consent_records' => 1, 'abandoned_signups' => 0]);
});
