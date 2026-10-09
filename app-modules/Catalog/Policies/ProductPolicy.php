<?php

declare(strict_types=1);

namespace Modules\Catalog\Policies;

use App\Models\User;
use Modules\Catalog\Infrastructure\Models\Product;

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

    /**
     * Publishing happens once, so a published product offers it no more.
     */
    public function publish(User $user, Product $product): bool
    {
        return $user->can('products.publish') && ! $product->isPublished();
    }
}
