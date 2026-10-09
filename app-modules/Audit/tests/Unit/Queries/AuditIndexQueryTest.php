<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Http\Request;
use Modules\Audit\Queries\AuditIndexQuery;
use Spatie\Activitylog\Models\Activity;
use Spatie\QueryBuilder\Exceptions\InvalidSortQuery;

/**
 * @param  array<string, mixed>  $query
 */
function auditIndexQuery(array $query = []): AuditIndexQuery
{
    return new AuditIndexQuery(Request::create('/admin/audit', 'GET', $query));
}

it('filters by search on the description, log name and event', function (): void {
    activity('users')->event('created')->log('user created');
    activity('roles')->event('deleted')->log('role removed');
    activity('system')->log('cache cleared');

    $byDescription = auditIndexQuery(['filter' => ['search' => 'cache cleared']])->builder()->get();
    $byLogName = auditIndexQuery(['filter' => ['search' => 'roles']])->builder()->get();
    $byEvent = auditIndexQuery(['filter' => ['search' => 'deleted']])->builder()->get();

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

    $byName = auditIndexQuery(['filter' => ['search' => 'Zelda']])->builder()->get();
    $byEmail = auditIndexQuery(['filter' => ['search' => 'bob@example.com']])->builder()->get();

    expect($byName->pluck('description')->all())->toBe(['user created'])
        ->and($byEmail->pluck('description')->all())->toBe(['user updated']);
});

it('treats a search value containing commas as a single term', function (): void {
    activity('users')->log('created, then verified');
    activity('users')->log('created');

    $results = auditIndexQuery(['filter' => ['search' => 'created, then verified']])->builder()->get();

    expect($results->pluck('description')->all())->toBe(['created, then verified']);
});

it('filters by the exact log name including comma-separated values', function (): void {
    activity('users')->log('user created');
    activity('roles')->log('role created');
    activity('system')->log('cache cleared');

    $single = auditIndexQuery(['filter' => ['log_name' => 'users']])->builder()->get();
    $multiple = auditIndexQuery(['filter' => ['log_name' => 'users,roles']])->builder()->get();

    expect($single->pluck('description')->all())->toBe(['user created'])
        ->and($multiple->pluck('description')->all())->toEqualCanonicalizing(['user created', 'role created']);
});

it('filters by the exact event including comma-separated values', function (): void {
    activity('users')->event('created')->log('user created');
    activity('users')->event('updated')->log('user updated');
    activity('users')->event('deleted')->log('user deleted');

    $single = auditIndexQuery(['filter' => ['event' => 'updated']])->builder()->get();
    $multiple = auditIndexQuery(['filter' => ['event' => 'created,deleted']])->builder()->get();

    expect($single->pluck('description')->all())->toBe(['user updated'])
        ->and($multiple->pluck('description')->all())->toEqualCanonicalizing(['user created', 'user deleted']);
});

it('sorts by the allowed created_at sort', function (): void {
    activity('users')->log('older entry');
    activity('users')->log('newer entry');

    Activity::query()->where('description', 'older entry')->update(['created_at' => now()->subWeek()]);

    $ascending = auditIndexQuery(['sort' => 'created_at'])->builder()->get();
    $descending = auditIndexQuery(['sort' => '-created_at'])->builder()->get();

    expect($ascending->first()?->description)->toBe('older entry')
        ->and($descending->first()?->description)->toBe('newer entry');
});

it('rejects sorts outside the whitelist', function (): void {
    activity('users')->log('user created');

    expect(fn () => auditIndexQuery(['sort' => 'description'])->builder()->get())
        ->toThrow(InvalidSortQuery::class);
});

it('sorts by newest first by default', function (): void {
    activity('users')->log('older entry');
    activity('users')->log('newer entry');

    Activity::query()->where('description', 'older entry')->update(['created_at' => now()->subWeek()]);

    $results = auditIndexQuery()->builder()->get();

    expect($results->pluck('description')->all())->toBe(['newer entry', 'older entry']);
});

it('paginates with a default of 15 per page', function (): void {
    foreach (range(1, 16) as $index) {
        activity('users')->log('entry '.$index);
    }

    $paginator = auditIndexQuery()->paginate();

    expect($paginator->perPage())->toBe(15)
        ->and($paginator->total())->toBe(16);
});

it('respects per_page within the allowed bounds', function (): void {
    activity('users')->log('user created');

    expect(auditIndexQuery(['per_page' => 25])->paginate()->perPage())->toBe(25)
        ->and(auditIndexQuery(['per_page' => 500])->paginate()->perPage())->toBe(100)
        ->and(auditIndexQuery(['per_page' => 0])->paginate()->perPage())->toBe(1);
});

it('eager loads the causer and subject', function (): void {
    $causer = User::factory()->create();
    $subject = User::factory()->create();

    activity('users')->performedOn($subject)->causedBy($causer)->log('user created');

    $first = auditIndexQuery()->paginate()->first();

    expect($first?->relationLoaded('causer'))->toBeTrue()
        ->and($first?->relationLoaded('subject'))->toBeTrue()
        ->and($first?->causer?->is($causer))->toBeTrue()
        ->and($first?->subject?->is($subject))->toBeTrue();
});
