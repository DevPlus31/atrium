<?php

declare(strict_types=1);

namespace Modules\Shop\Domain\Events;

use App\Domain\Contracts\DomainEvent;

final readonly class OrderStatusChanged implements DomainEvent
{
    public function __construct(
        public string $orderId,
        public string $from,
        public string $to,
    ) {
        //
    }
}
