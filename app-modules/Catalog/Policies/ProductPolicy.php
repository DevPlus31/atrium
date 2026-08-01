<?php

declare(strict_types=1);

namespace Modules\Catalog\Policies;

use App\Models\User;

final readonly class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('products.view');
    }

    public function create(User $user): bool
    {
        return $user->can('products.create');
    }

    public function update(User $user): bool
    {
        return $user->can('products.update');
    }

    public function delete(User $user): bool
    {
        return $user->can('products.delete');
    }

    public function publish(User $user): bool
    {
        return $user->can('products.publish');
    }
}
