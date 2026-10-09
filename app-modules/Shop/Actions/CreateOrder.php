<?php

declare(strict_types=1);

namespace Modules\Shop\Actions;

use App\Domain\ValueObjects\Money;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Modules\Shop\Domain\Repositories\OrderRepository;
use Modules\Shop\Domain\ValueObjects\OrderNumber;
use Modules\Shop\Infrastructure\Models\Order;
use Throwable;

final readonly class CreateOrder
{
    private const int ATTEMPTS = 3;

    public function __construct(private OrderRepository $orders)
    {
        //
    }

    public function handle(string $customerEmail, int $totalCents, string $currency): Order
    {
        $total = new Money($totalCents, $currency);

        // A random order number may, very rarely, already exist; the unique
        // index rejects it and the whole transaction is retried.
        $order = retry(self::ATTEMPTS, fn (): Order => DB::transaction(function () use ($customerEmail, $total): Order {
            $order = Order::place(OrderNumber::generate(), $customerEmail, $total);

            $this->orders->save($order);

            activity('shop')
                ->performedOn($order)
                ->event('created')
                ->withProperties([
                    'attributes' => [
                        'number' => $order->number,
                        'customer_email' => $order->customer_email,
                        'total_cents' => $order->total_cents,
                        'currency' => $order->currency,
                    ],
                ])
                ->log('created');

            return $order;
        }), when: static fn (Throwable $exception): bool => $exception instanceof UniqueConstraintViolationException);

        $order->flushDomainEvents();

        return $order;
    }
}
