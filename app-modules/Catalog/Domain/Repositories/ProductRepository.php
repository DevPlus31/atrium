<?php

declare(strict_types=1);

namespace Modules\Catalog\Domain\Repositories;

use App\Domain\Contracts\Repository;
use Modules\Catalog\Infrastructure\Models\Product;

interface ProductRepository extends Repository
{
    public function save(Product $product): void;

    public function delete(Product $product): void;
}
