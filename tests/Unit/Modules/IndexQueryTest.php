<?php

declare(strict_types=1);

use App\Models\User;
use Modules\Users\Queries\UsersIndexQuery;

it('breaks sort ties on the primary key so pages and exports are stable', function (string $sort, string $expected): void {
    $query = indexQuery(UsersIndexQuery::class, ['sort' => $sort]);

    expect($query->builder()->toSql())->toEndWith($expected);
})->with([
    'default sort' => ['-created_at', 'order by "created_at" desc, "users"."id" desc'],
    'name sort' => ['name', 'order by "name" asc, "users"."id" asc'],
]);

it('paginates 15 per page by default and keeps per_page within 1 to 100', function (): void {
    User::factory()->count(16)->create();

    $default = indexQuery(UsersIndexQuery::class)->paginate();

    expect($default->perPage())->toBe(15)
        ->and($default->total())->toBe(16)
        ->and(indexQuery(UsersIndexQuery::class, ['per_page' => 25])->paginate()->perPage())->toBe(25)
        ->and(indexQuery(UsersIndexQuery::class, ['per_page' => 500])->paginate()->perPage())->toBe(100)
        ->and(indexQuery(UsersIndexQuery::class, ['per_page' => 0])->paginate()->perPage())->toBe(1);
});

it('treats a search value containing commas as a single term', function (): void {
    User::factory()->create(['name' => 'Doe, Jane', 'email' => 'jane@example.com']);
    User::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);

    $results = indexQuery(UsersIndexQuery::class, ['filter' => ['search' => 'Doe, Jane']])->builder()->get();

    expect($results->pluck('email')->all())->toBe(['jane@example.com']);
});
