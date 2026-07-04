<?php

declare(strict_types=1);

use App\Modules\PermissionRegistry;

it('collects declared permissions', function (): void {
    $registry = new PermissionRegistry();

    $registry->declare('users.view', roles: ['admin']);
    $registry->declare('users.delete');

    expect($registry->permissions())->toBe(['users.view', 'users.delete']);
});

it('merges default roles when a permission is declared twice', function (): void {
    $registry = new PermissionRegistry();

    $registry->declare('users.view', roles: ['admin']);
    $registry->declare('users.view', roles: ['admin', 'editor']);

    expect($registry->roleAssignments())->toBe([
        'users.view' => ['admin', 'editor'],
    ]);
});

it('declares permissions without default roles', function (): void {
    $registry = new PermissionRegistry();

    $registry->declare('users.delete');

    expect($registry->roleAssignments())->toBe([
        'users.delete' => [],
    ]);
});
