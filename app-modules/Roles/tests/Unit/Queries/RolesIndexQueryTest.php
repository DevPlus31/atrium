<?php

declare(strict_types=1);

use App\Models\User;
use Modules\Roles\Queries\RolesIndexQuery;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\QueryBuilder\Exceptions\InvalidSortQuery;

it('filters by search on the role name', function (): void {
    Role::findOrCreate('content-editor');
    Role::findOrCreate('moderator');
    Role::findOrCreate('viewer');

    $results = indexQuery(RolesIndexQuery::class, ['filter' => ['search' => 'editor']])->builder()->get();

    expect($results->pluck('name')->all())->toBe(['content-editor']);
});

it('sorts by the allowed sorts', function (): void {
    Role::findOrCreate('middle');
    Role::findOrCreate('aaa');
    Role::findOrCreate('zzz');

    Role::query()->where('name', 'zzz')->update(['created_at' => now()->subWeek()]);

    $byName = indexQuery(RolesIndexQuery::class, ['sort' => 'name'])->builder()->get();
    $byNameDesc = indexQuery(RolesIndexQuery::class, ['sort' => '-name'])->builder()->get();
    $byCreatedAt = indexQuery(RolesIndexQuery::class, ['sort' => 'created_at'])->builder()->get();

    expect($byName->first()?->name)->toBe('aaa')
        ->and($byNameDesc->first()?->name)->toBe('zzz')
        ->and($byCreatedAt->first()?->name)->toBe('zzz');
});

it('rejects sorts outside the whitelist', function (): void {
    Role::findOrCreate('editor');

    expect(fn () => indexQuery(RolesIndexQuery::class, ['sort' => 'guard_name'])->builder()->get())
        ->toThrow(InvalidSortQuery::class);
});

it('sorts by name by default', function (): void {
    Role::findOrCreate('zebra');
    Role::findOrCreate('alpha');

    $results = indexQuery(RolesIndexQuery::class)->builder()->get();

    expect($results->pluck('name')->all())->toBe(['alpha', 'zebra']);
});

it('eager loads permissions and the users count', function (): void {
    Permission::findOrCreate('users.view');

    $role = Role::findOrCreate('editor');
    $role->givePermissionTo('users.view');

    $user = User::factory()->create();
    $user->assignRole('editor');

    $results = indexQuery(RolesIndexQuery::class)->paginate();
    $first = $results->first();

    expect($first?->relationLoaded('permissions'))->toBeTrue()
        ->and($first?->getAttribute('users_count'))->toBe(1);
});
