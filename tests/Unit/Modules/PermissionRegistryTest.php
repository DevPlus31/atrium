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

it('rejects permission names that could shadow a policy ability', function (string $name): void {
    new PermissionRegistry()->declare($name);
})->with(['update', 'orders', 'Orders.View', 'orders view', 'orders.'])->throws(InvalidArgumentException::class, 'must be namespaced');

it('accepts namespaced permission names', function (string $name): void {
    $registry = new PermissionRegistry();
    $registry->declare($name);

    expect($registry->has($name))->toBeTrue();
})->with(['orders.view', 'system.pulse.view', 'test-module.view']);
