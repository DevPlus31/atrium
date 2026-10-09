<?php

declare(strict_types=1);

beforeEach(function (): void {
    $this->artisan('admin:sync-permissions')->assertSuccessful();
});

it('ships page-level abilities that follow the admin permissions', function (string $route, string $ability, string $permission): void {
    assertPageAbilityFollowsPermission($route, $ability, $permission);
})->with([
    'roles create' => ['admin.roles.index', 'create', 'roles.create'],
    'users create' => ['admin.users.index', 'create', 'users.create'],
    'users export' => ['admin.users.index', 'export', 'users.export'],
]);
