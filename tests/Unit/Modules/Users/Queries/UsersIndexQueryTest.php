<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Http\Request;
use Modules\Users\Queries\UsersIndexQuery;
use Spatie\Permission\Models\Role;

/**
 * @param  array<string, mixed>  $query
 */
function usersIndexQuery(array $query = []): UsersIndexQuery
{
    return new UsersIndexQuery(Request::create('/admin/users', 'GET', $query));
}

it('filters by search across name and email', function (): void {
    User::factory()->create(['name' => 'Alice Wonder', 'email' => 'alice@example.com']);
    User::factory()->create(['name' => 'Bob Builder', 'email' => 'bob@example.com']);
    User::factory()->create(['name' => 'Carol Alice', 'email' => 'carol@example.com']);

    $results = usersIndexQuery(['filter' => ['search' => 'alice']])->builder()->get();

    expect($results->pluck('email')->all())->toEqualCanonicalizing([
        'alice@example.com',
        'carol@example.com',
    ]);
});

it('treats a search value containing commas as a single term', function (): void {
    User::factory()->create(['name' => 'Doe, Jane', 'email' => 'jane@example.com']);
    User::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);

    $results = usersIndexQuery(['filter' => ['search' => 'Doe, Jane']])->builder()->get();

    expect($results->pluck('email')->all())->toBe(['jane@example.com']);
});

it('filters by roles from csv values', function (): void {
    Role::findOrCreate('editor');
    Role::findOrCreate('viewer');

    $editor = User::factory()->create();
    $editor->assignRole('editor');

    $viewer = User::factory()->create();
    $viewer->assignRole('viewer');

    User::factory()->create();

    $single = usersIndexQuery(['filter' => ['role' => 'editor']])->builder()->get();
    $multiple = usersIndexQuery(['filter' => ['role' => 'editor,viewer']])->builder()->get();

    expect($single->pluck('id')->all())->toBe([$editor->id])
        ->and($multiple->pluck('id')->all())->toEqualCanonicalizing([$editor->id, $viewer->id]);
});

it('filters by verification status', function (): void {
    $verified = User::factory()->create();
    $unverified = User::factory()->unverified()->create();

    $yes = usersIndexQuery(['filter' => ['verified' => 'yes']])->builder()->get();
    $no = usersIndexQuery(['filter' => ['verified' => 'no']])->builder()->get();
    $other = usersIndexQuery(['filter' => ['verified' => 'maybe']])->builder()->get();

    expect($yes->pluck('id')->all())->toBe([$verified->id])
        ->and($no->pluck('id')->all())->toBe([$unverified->id])
        ->and($other)->toHaveCount(2);
});

it('sorts by the allowed sorts', function (): void {
    User::factory()->create(['name' => 'Middle', 'email' => 'm@example.com']);
    User::factory()->create(['name' => 'Aaa', 'email' => 'z@example.com']);
    User::factory()->create(['name' => 'Zzz', 'email' => 'a@example.com']);

    $byName = usersIndexQuery(['sort' => 'name'])->builder()->get();
    $byNameDesc = usersIndexQuery(['sort' => '-name'])->builder()->get();
    $byEmail = usersIndexQuery(['sort' => 'email'])->builder()->get();

    expect($byName->first()?->name)->toBe('Aaa')
        ->and($byNameDesc->first()?->name)->toBe('Zzz')
        ->and($byEmail->first()?->email)->toBe('a@example.com');
});

it('sorts by newest first by default', function (): void {
    User::factory()->create(['created_at' => now()->subWeek()]);
    $newest = User::factory()->create(['created_at' => now()->addHour()]);

    $results = usersIndexQuery()->builder()->get();

    expect($results->first()?->id)->toBe($newest->id);
});

it('paginates with a default of 15 per page', function (): void {
    User::factory()->count(16)->create();

    $paginator = usersIndexQuery()->paginate();

    expect($paginator->perPage())->toBe(15)
        ->and($paginator->total())->toBe(16);
});

it('respects per_page within the allowed bounds', function (): void {
    User::factory()->count(3)->create();

    expect(usersIndexQuery(['per_page' => 25])->paginate()->perPage())->toBe(25)
        ->and(usersIndexQuery(['per_page' => 500])->paginate()->perPage())->toBe(100)
        ->and(usersIndexQuery(['per_page' => 0])->paginate()->perPage())->toBe(1);
});

it('eager loads roles on the index results', function (): void {
    Role::findOrCreate('editor');
    $user = User::factory()->create();
    $user->assignRole('editor');

    $results = usersIndexQuery()->paginate();

    expect($results->first()?->relationLoaded('roles'))->toBeTrue();
});
