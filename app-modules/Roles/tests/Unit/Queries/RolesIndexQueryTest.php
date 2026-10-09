<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Http\Request;
use Modules\Roles\Queries\RolesIndexQuery;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\QueryBuilder\Exceptions\InvalidSortQuery;

/**
 * @param  array<string, mixed>  $query
 */
function rolesIndexQuery(array $query = []): RolesIndexQuery
{
    return new RolesIndexQuery(Request::create('/admin/roles', 'GET', $query));
}

it('filters by search on the role name', function (): void {
    Role::findOrCreate('content-editor');
    Role::findOrCreate('moderator');
    Role::findOrCreate('viewer');

    $results = rolesIndexQuery(['filter' => ['search' => 'editor']])->builder()->get();

    expect($results->pluck('name')->all())->toBe(['content-editor']);
});

it('treats a search value containing commas as a single term', function (): void {
    Role::findOrCreate('editor, senior');
    Role::findOrCreate('editor');

    $results = rolesIndexQuery(['filter' => ['search' => 'editor, senior']])->builder()->get();

    expect($results->pluck('name')->all())->toBe(['editor, senior']);
});

it('sorts by the allowed sorts', function (): void {
    Role::findOrCreate('middle');
    Role::findOrCreate('aaa');
    Role::findOrCreate('zzz');

    Role::query()->where('name', 'zzz')->update(['created_at' => now()->subWeek()]);

    $byName = rolesIndexQuery(['sort' => 'name'])->builder()->get();
    $byNameDesc = rolesIndexQuery(['sort' => '-name'])->builder()->get();
    $byCreatedAt = rolesIndexQuery(['sort' => 'created_at'])->builder()->get();

    expect($byName->first()?->name)->toBe('aaa')
        ->and($byNameDesc->first()?->name)->toBe('zzz')
        ->and($byCreatedAt->first()?->name)->toBe('zzz');
});

it('rejects sorts outside the whitelist', function (): void {
    Role::findOrCreate('editor');

    expect(fn () => rolesIndexQuery(['sort' => 'guard_name'])->builder()->get())
        ->toThrow(InvalidSortQuery::class);
});

it('sorts by name by default', function (): void {
    Role::findOrCreate('zebra');
    Role::findOrCreate('alpha');

    $results = rolesIndexQuery()->builder()->get();

    expect($results->pluck('name')->all())->toBe(['alpha', 'zebra']);
});

it('paginates with a default of 15 per page', function (): void {
    foreach (range(1, 16) as $index) {
        Role::findOrCreate('role-'.$index);
    }

    $paginator = rolesIndexQuery()->paginate();

    expect($paginator->perPage())->toBe(15)
        ->and($paginator->total())->toBe(16);
});

it('respects per_page within the allowed bounds', function (): void {
    Role::findOrCreate('editor');

    expect(rolesIndexQuery(['per_page' => 25])->paginate()->perPage())->toBe(25)
        ->and(rolesIndexQuery(['per_page' => 500])->paginate()->perPage())->toBe(100)
        ->and(rolesIndexQuery(['per_page' => 0])->paginate()->perPage())->toBe(1);
});

it('eager loads permissions and the users count', function (): void {
    Permission::findOrCreate('users.view');

    $role = Role::findOrCreate('editor');
    $role->givePermissionTo('users.view');

    $user = User::factory()->create();
    $user->assignRole('editor');

    $results = rolesIndexQuery()->paginate();
    $first = $results->first();

    expect($first?->relationLoaded('permissions'))->toBeTrue()
        ->and($first?->getAttribute('users_count'))->toBe(1);
});
