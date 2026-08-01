<?php

declare(strict_types=1);

namespace Modules\Catalog\Domain\Events;

use App\Domain\Contracts\DomainEvent;

final readonly class ProductPublished implements DomainEvent
{
    public function __construct(
        public string $productId,
        public string $sku,
    ) {
        //
    }
}
