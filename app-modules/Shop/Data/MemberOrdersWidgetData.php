<?php

declare(strict_types=1);

namespace Modules\Shop\Data;

use Modules\Shop\Domain\Enums\OrderStatus;
use Spatie\LaravelData\Data;

final class MemberOrdersWidgetData extends Data
{
    /**
     * @param  list<array{id: string, number: string, status: OrderStatus, total_cents: int, currency: string, placed_at: string}>  $orders
     */
    public function __construct(
        public int $total,
        public array $orders,
    ) {
        //
    }
}
