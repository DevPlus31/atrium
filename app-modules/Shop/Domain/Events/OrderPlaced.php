<?php

declare(strict_types=1);

namespace Modules\Shop\Domain\Events;

use App\Domain\Contracts\DomainEvent;

final readonly class OrderPlaced implements DomainEvent
{
    public function __construct(
        public string $orderId,
        public string $number,
        public int $totalCents,
        public string $currency,
    ) {
        //
    }
}
