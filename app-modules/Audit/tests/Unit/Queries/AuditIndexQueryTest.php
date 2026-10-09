<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Modules\Audit\Queries\AuditIndexQuery;
use Spatie\Activitylog\Models\Activity;
use Spatie\QueryBuilder\Exceptions\InvalidSortQuery;

it('filters by search on the description, log name and event', function (): void {
    activity('users')->event('created')->log('user created');
    activity('roles')->event('deleted')->log('role removed');
    activity('system')->log('cache cleared');

    $byDescription = indexQuery(AuditIndexQuery::class, ['filter' => ['search' => 'cache cleared']])->builder()->get();
    $byLogName = indexQuery(AuditIndexQuery::class, ['filter' => ['search' => 'roles']])->builder()->get();
    $byEvent = indexQuery(AuditIndexQuery::class, ['filter' => ['search' => 'deleted']])->builder()->get();

    expect($byDescription->pluck('description')->all())->toBe(['cache cleared'])
        ->and($byLogName->pluck('description')->all())->toBe(['role removed'])
        ->and($byEvent->pluck('description')->all())->toBe(['role removed']);
});

it('filters by search on the causer name and email', function (): void {
    $causer = User::factory()->create(['name' => 'Zelda Zonneveld', 'email' => 'zelda@example.com']);
    $other = User::factory()->create(['name' => 'Bob Berg', 'email' => 'bob@example.com']);

    activity('users')->causedBy($causer)->log('user created');
    activity('users')->causedBy($other)->log('user updated');
    activity('users')->log('user pruned');

    $byName = indexQuery(AuditIndexQuery::class, ['filter' => ['search' => 'Zelda']])->builder()->get();
    $byEmail = indexQuery(AuditIndexQuery::class, ['filter' => ['search' => 'bob@example.com']])->builder()->get();

    expect($byName->pluck('description')->all())->toBe(['user created'])
        ->and($byEmail->pluck('description')->all())->toBe(['user updated']);
});

it('filters by the exact log name including comma-separated values', function (): void {
    activity('users')->log('user created');
    activity('roles')->log('role created');
    activity('system')->log('cache cleared');

    $single = indexQuery(AuditIndexQuery::class, ['filter' => ['log_name' => 'users']])->builder()->get();
    $multiple = indexQuery(AuditIndexQuery::class, ['filter' => ['log_name' => 'users,roles']])->builder()->get();

    expect($single->pluck('description')->all())->toBe(['user created'])
        ->and($multiple->pluck('description')->all())->toEqualCanonicalizing(['user created', 'role created']);
});

it('filters by the exact event including comma-separated values', function (): void {
    activity('users')->event('created')->log('user created');
    activity('users')->event('updated')->log('user updated');
    activity('users')->event('deleted')->log('user deleted');

    $single = indexQuery(AuditIndexQuery::class, ['filter' => ['event' => 'updated']])->builder()->get();
    $multiple = indexQuery(AuditIndexQuery::class, ['filter' => ['event' => 'created,deleted']])->builder()->get();

    expect($single->pluck('description')->all())->toBe(['user updated'])
        ->and($multiple->pluck('description')->all())->toEqualCanonicalizing(['user created', 'user deleted']);
});

it('sorts by the allowed created_at sort', function (): void {
    activity('users')->log('older entry');
    activity('users')->log('newer entry');

    Activity::query()->where('description', 'older entry')->update(['created_at' => now()->subWeek()]);

    $ascending = indexQuery(AuditIndexQuery::class, ['sort' => 'created_at'])->builder()->get();
    $descending = indexQuery(AuditIndexQuery::class, ['sort' => '-created_at'])->builder()->get();

    expect($ascending->first()?->description)->toBe('older entry')
        ->and($descending->first()?->description)->toBe('newer entry');
});

it('rejects sorts outside the whitelist', function (): void {
    activity('users')->log('user created');

    expect(fn () => indexQuery(AuditIndexQuery::class, ['sort' => 'description'])->builder()->get())
        ->toThrow(InvalidSortQuery::class);
});

it('sorts by newest first by default', function (): void {
    activity('users')->log('older entry');
    activity('users')->log('newer entry');

    Activity::query()->where('description', 'older entry')->update(['created_at' => now()->subWeek()]);

    $results = indexQuery(AuditIndexQuery::class)->builder()->get();

    expect($results->pluck('description')->all())->toBe(['newer entry', 'older entry']);
});

it('eager loads only the causer (the page shows the subject by type and id)', function (): void {
    $causer = User::factory()->create();
    $subject = User::factory()->create();

    activity('users')->performedOn($subject)->causedBy($causer)->log('user created');

    $first = indexQuery(AuditIndexQuery::class)->paginate()->first();

    expect($first?->relationLoaded('causer'))->toBeTrue()
        ->and($first?->relationLoaded('subject'))->toBeFalse()
        ->and($first?->causer?->is($causer))->toBeTrue()
        ->and($first?->subject_id)->toBe($subject->id);
});

it('has the event column indexed for the filter and its facet', function (): void {
    $indexed = collect(Schema::getIndexes('activity_log'))
        ->contains(fn (array $index): bool => $index['columns'] === ['event']);

    expect($indexed)->toBeTrue();
});
