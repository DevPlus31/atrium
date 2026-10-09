<?php

declare(strict_types=1);

namespace Modules\Shop\Actions;

use App\Domain\ValueObjects\Money;
use Illuminate\Support\Facades\DB;
use Modules\Shop\Domain\Repositories\OrderRepository;
use Modules\Shop\Infrastructure\Models\Order;

final readonly class UpdateOrder
{
    public function __construct(private OrderRepository $orders)
    {
        //
    }

    public function handle(Order $order, string $customerEmail, int $totalCents, string $currency): Order
    {
        $total = new Money($totalCents, $currency);

        return DB::transaction(function () use ($order, $customerEmail, $total): Order {
            $order = $this->orders->lockForUpdate($order);

            $order->revise($customerEmail, $total);

            $this->orders->save($order);

            activity('shop')
                ->performedOn($order)
                ->event('updated')
                ->withProperties([
                    'attributes' => [
                        'customer_email' => $order->customer_email,
                        'total_cents' => $total->amount,
                        'currency' => $total->currency,
                    ],
                ])
                ->log('updated');

            return $order->refresh();
        });
    }
}
