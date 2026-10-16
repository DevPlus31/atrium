<?php

declare(strict_types=1);

use App\Actions\ExportPersonalData;
use App\Models\ConsentRecord;
use App\Models\User;
use App\Modules\AuditLog;
use App\Modules\PersonalDataRegistry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

/**
 * A user with a record of every kind the shell exports.
 */
function userWithEveryKindOfPersonalData(): User
{
    $user = User::factory()->create(['name' => 'Ada', 'email' => 'ada@example.com', 'locale' => 'fr']);
    $user->assignRole(Role::findOrCreate('editor'));
    $user->passkeys()->create(['name' => 'Laptop', 'credential_id' => 'credential-1', 'credential' => ['aaguid' => 'x']]);
    $user->createToken('Script', ['orders.view']);
    $user->notifications()->create(['id' => (string) Str::uuid(), 'type' => 'example', 'data' => ['title' => 'Hello']]);
    DB::table('sessions')->insert(['id' => 'one', 'user_id' => $user->id, 'ip_address' => '203.0.113.9', 'user_agent' => 'Firefox', 'payload' => '', 'last_activity' => now()->getTimestamp()]);
    DB::table('sessions')->insert(['id' => 'two', 'user_id' => $user->id, 'payload' => '', 'last_activity' => now()->subHour()->getTimestamp()]);
    ConsentRecord::factory()->create(['user_id' => $user->id, 'granted' => ['analytics'], 'gpc' => true]);
    AuditLog::record(log: 'users', event: 'updated', subject: User::factory()->create(), properties: ['attributes' => ['email' => 'someone@else.test']], causer: $user);
    AuditLog::record(log: 'users', event: 'updated', subject: $user);

    return $user;
}

it('exports the account without its secrets', function (): void {
    $export = resolve(ExportPersonalData::class)(userWithEveryKindOfPersonalData());

    expect($export['exported_at'])->toBe(now()->toIso8601String())
        ->and($export['account'])->toMatchArray([
            'name' => 'Ada',
            'email' => 'ada@example.com',
            'locale' => 'fr',
            'profile_photo' => null,
            'two_factor_enabled' => true,
            'terms_version' => config('privacy.terms_version'),
        ])
        ->and($export['account'])->not->toHaveKeys(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'])
        ->and($export['roles'])->toBe(['editor']);
});

it('exports the sign-in methods, sessions, notifications and cookie choices', function (): void {
    $export = resolve(ExportPersonalData::class)(userWithEveryKindOfPersonalData());

    expect($export['passkeys'])->toBe([['name' => 'Laptop', 'created_at' => now()->toIso8601String(), 'last_used_at' => null]])
        ->and($export['api_tokens'])->toHaveCount(1)
        ->and($export['api_tokens'][0])->toMatchArray(['name' => 'Script', 'abilities' => ['orders.view']])
        ->and($export['api_tokens'][0])->not->toHaveKey('token')
        ->and($export['sessions'])->toContain(['ip_address' => '203.0.113.9', 'user_agent' => 'Firefox', 'last_active' => now()->toIso8601String()])
        ->and($export['sessions'])->toContain(['ip_address' => null, 'user_agent' => null, 'last_active' => now()->subHour()->toIso8601String()])
        ->and($export['notifications'])->toBe([['data' => ['title' => 'Hello'], 'read_at' => null, 'created_at' => now()->toIso8601String()]])
        ->and($export['cookie_choices'])->toBe([[
            'version' => config('privacy.consent.version'),
            'granted' => ['analytics'],
            'global_privacy_control' => true,
            'created_at' => now()->toIso8601String(),
        ]]);
});

it('exports the audit entries by or about the user without their recorded values', function (): void {
    $export = resolve(ExportPersonalData::class)(userWithEveryKindOfPersonalData());

    expect($export['activity'])->toBe([
        ['log' => 'users', 'event' => 'updated', 'by_you' => true, 'created_at' => now()->toIso8601String()],
        ['log' => 'users', 'event' => 'updated', 'by_you' => false, 'created_at' => now()->toIso8601String()],
    ])->and(json_encode($export))->not->toContain('someone@else.test');
});

it('includes every module’s records', function (): void {
    resolve(PersonalDataRegistry::class)->add('test', 'test.things', fn (User $user): array => [['owner' => $user->email]]);

    $user = User::factory()->create(['email' => 'ada@example.com']);

    expect(resolve(ExportPersonalData::class)($user)['modules'])->toMatchArray(['test.things' => [['owner' => 'ada@example.com']]]);
});

it('records the request in the audit log', function (): void {
    $user = User::factory()->create();

    $export = resolve(ExportPersonalData::class)($user);

    $activity = Activity::query()->where('event', 'personal-data-exported')->sole();

    expect($export)->toBeArray()
        ->and($activity->log_name)->toBe('privacy')
        ->and($activity->causer_id)->toBe($user->id)
        ->and($activity->subject_id)->toBe($user->id);
});
