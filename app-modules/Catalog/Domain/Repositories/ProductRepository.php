<?php

declare(strict_types=1);

namespace Modules\Catalog\Domain\Repositories;

use App\Domain\Contracts\Repository;
use Modules\Catalog\Infrastructure\Models\Product;

interface ProductRepository extends Repository
{
    /**
     * Re-read the product inside the current transaction, locking its row so
     * two simultaneous changes (e.g. publishing) check the latest state.
     */
    public function lockForUpdate(Product $product): Product;

    public function save(Product $product): void;

    public function delete(Product $product): void;
}
