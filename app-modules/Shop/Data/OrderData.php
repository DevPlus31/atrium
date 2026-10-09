<?php

declare(strict_types=1);

namespace Modules\Shop\Data;

use Illuminate\Support\Facades\Auth;
use Modules\Shop\Domain\Enums\OrderStatus;
use Modules\Shop\Infrastructure\Models\Order;
use Spatie\LaravelData\Data;

final class OrderData extends Data
{
    /**
     * @param  array{update: bool, delete: bool}  $can
     * @param  list<OrderStatus>  $transitions
     */
    public function __construct(
        public string $id,
        public string $number,
        public string $customer_email,
        public OrderStatus $status,
        public int $total_cents,
        public string $currency,
        public ?string $paid_at,
        public ?string $shipped_at,
        public ?string $cancelled_at,
        public string $created_at,
        public array $can,
        public array $transitions,
    ) {
        //
    }

    public static function fromModel(Order $order): self
    {
        $viewer = Auth::user();
        $canTransition = $viewer?->can('transition', $order) ?? false;

        return new self(
            id: $order->id,
            number: $order->number,
            customer_email: $order->customer_email,
            status: $order->status,
            total_cents: $order->total_cents,
            currency: $order->currency,
            paid_at: $order->paid_at?->toIso8601String(),
            shipped_at: $order->shipped_at?->toIso8601String(),
            cancelled_at: $order->cancelled_at?->toIso8601String(),
            created_at: $order->created_at->toIso8601String(),
            can: [
                'update' => $viewer?->can('update', $order) ?? false,
                'delete' => $viewer?->can('delete', $order) ?? false,
            ],
            transitions: $canTransition ? $order->status->transitions() : [],
        );
    }
}
