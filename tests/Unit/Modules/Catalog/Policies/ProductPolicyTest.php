<?php

declare(strict_types=1);

use App\Models\User;
use Modules\Catalog\Policies\ProductPolicy;
use Spatie\Permission\Models\Permission;

beforeEach(function (): void {
    foreach (['products.view', 'products.create', 'products.update', 'products.delete', 'products.publish'] as $permission) {
        Permission::findOrCreate($permission);
    }

    $this->policy = new ProductPolicy();
});

it('gates each ability behind its matching permission', function (string $ability, string $permission): void {
    $user = User::factory()->create();

    expect($this->policy->{$ability}($user))->toBeFalse();

    $user->givePermissionTo($permission);

    expect($this->policy->{$ability}($user->refresh()))->toBeTrue();
})->with([
    'viewAny' => ['viewAny', 'products.view'],
    'create' => ['create', 'products.create'],
    'update' => ['update', 'products.update'],
    'delete' => ['delete', 'products.delete'],
    'publish' => ['publish', 'products.publish'],
]);
