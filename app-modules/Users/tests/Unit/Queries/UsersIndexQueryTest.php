<?php

declare(strict_types=1);

use App\Models\User;
use Modules\Users\Queries\UsersIndexQuery;
use Spatie\Permission\Models\Role;

it('filters by search across name and email', function (): void {
    User::factory()->create(['name' => 'Alice Wonder', 'email' => 'alice@example.com']);
    User::factory()->create(['name' => 'Bob Builder', 'email' => 'bob@example.com']);
    User::factory()->create(['name' => 'Carol Alice', 'email' => 'carol@example.com']);

    $results = indexQuery(UsersIndexQuery::class, ['filter' => ['search' => 'alice']])->builder()->get();

    expect($results->pluck('email')->all())->toEqualCanonicalizing([
        'alice@example.com',
        'carol@example.com',
    ]);
});

it('filters by roles from csv values', function (): void {
    Role::findOrCreate('editor');
    Role::findOrCreate('viewer');

    $editor = User::factory()->create();
    $editor->assignRole('editor');

    $viewer = User::factory()->create();
    $viewer->assignRole('viewer');

    User::factory()->create();

    $single = indexQuery(UsersIndexQuery::class, ['filter' => ['role' => 'editor']])->builder()->get();
    $multiple = indexQuery(UsersIndexQuery::class, ['filter' => ['role' => 'editor,viewer']])->builder()->get();

    expect($single->pluck('id')->all())->toBe([$editor->id])
        ->and($multiple->pluck('id')->all())->toEqualCanonicalizing([$editor->id, $viewer->id]);
});

it('filters by verification status', function (): void {
    $verified = User::factory()->create();
    $unverified = User::factory()->unverified()->create();

    $yes = indexQuery(UsersIndexQuery::class, ['filter' => ['verified' => 'yes']])->builder()->get();
    $no = indexQuery(UsersIndexQuery::class, ['filter' => ['verified' => 'no']])->builder()->get();
    $other = indexQuery(UsersIndexQuery::class, ['filter' => ['verified' => 'maybe']])->builder()->get();

    expect($yes->pluck('id')->all())->toBe([$verified->id])
        ->and($no->pluck('id')->all())->toBe([$unverified->id])
        ->and($other)->toHaveCount(2);
});

it('sorts by the allowed sorts', function (): void {
    User::factory()->create(['name' => 'Middle', 'email' => 'm@example.com']);
    User::factory()->create(['name' => 'Aaa', 'email' => 'z@example.com']);
    User::factory()->create(['name' => 'Zzz', 'email' => 'a@example.com']);

    $byName = indexQuery(UsersIndexQuery::class, ['sort' => 'name'])->builder()->get();
    $byNameDesc = indexQuery(UsersIndexQuery::class, ['sort' => '-name'])->builder()->get();
    $byEmail = indexQuery(UsersIndexQuery::class, ['sort' => 'email'])->builder()->get();

    expect($byName->first()?->name)->toBe('Aaa')
        ->and($byNameDesc->first()?->name)->toBe('Zzz')
        ->and($byEmail->first()?->email)->toBe('a@example.com');
});

it('sorts by newest first by default', function (): void {
    User::factory()->create(['created_at' => now()->subWeek()]);
    $newest = User::factory()->create(['created_at' => now()->addHour()]);

    $results = indexQuery(UsersIndexQuery::class)->builder()->get();

    expect($results->first()?->id)->toBe($newest->id);
});

it('eager loads roles on the index results', function (): void {
    Role::findOrCreate('editor');
    $user = User::factory()->create();
    $user->assignRole('editor');

    $results = indexQuery(UsersIndexQuery::class)->paginate();

    expect($results->first()?->relationLoaded('roles'))->toBeTrue();
});
