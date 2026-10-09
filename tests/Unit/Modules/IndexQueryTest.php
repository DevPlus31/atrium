<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Modules\Users\Queries\UsersIndexQuery;

it('breaks sort ties on the primary key so pages and exports are stable', function (string $sort, string $expected): void {
    $query = new UsersIndexQuery(Request::create('/admin/users', 'GET', ['sort' => $sort]));

    expect($query->builder()->toSql())->toEndWith($expected);
})->with([
    'default sort' => ['-created_at', 'order by "created_at" desc, "users"."id" desc'],
    'name sort' => ['name', 'order by "name" asc, "users"."id" asc'],
]);
