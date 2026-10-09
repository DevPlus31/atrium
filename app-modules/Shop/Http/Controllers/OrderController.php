<?php

declare(strict_types=1);

namespace Modules\Shop\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Shop\Actions\CreateOrder;
use Modules\Shop\Actions\DeleteOrder;
use Modules\Shop\Actions\UpdateOrder;
use Modules\Shop\Data\OrderData;
use Modules\Shop\Domain\Enums\OrderStatus;
use Modules\Shop\Http\Requests\StoreOrderRequest;
use Modules\Shop\Http\Requests\UpdateOrderRequest;
use Modules\Shop\Infrastructure\Models\Order;
use Modules\Shop\Queries\OrdersIndexQuery;
use Modules\Shop\Settings\ShopSettings;
use Spatie\LaravelData\PaginatedDataCollection;

final readonly class OrderController
{
    #[Authorize('viewAny', Order::class)]
    public function index(OrdersIndexQuery $query): Response
    {
        return Inertia::render('shop::index', [
            'orders' => OrderData::collect($query->paginate(), PaginatedDataCollection::class),
            'statuses' => array_column(OrderStatus::cases(), 'value'),
            'can' => ['create' => Gate::allows('create', Order::class)],
        ]);
    }

    #[Authorize('create', Order::class)]
    public function create(ShopSettings $settings): Response
    {
        return Inertia::render('shop::create', [
            'defaultCurrency' => $settings->default_currency,
        ]);
    }

    #[Authorize('create', Order::class)]
    public function store(StoreOrderRequest $request, CreateOrder $action): RedirectResponse
    {
        $action->handle(
            customerEmail: $request->customerEmail(),
            totalCents: $request->totalCents(),
            currency: $request->currency(),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Order created.')]);

        return to_route('admin.orders.index');
    }

    #[Authorize('update', 'order')]
    public function edit(Order $order): Response
    {
        return Inertia::render('shop::edit', [
            'order' => OrderData::from($order),
        ]);
    }

    #[Authorize('update', 'order')]
    public function update(UpdateOrderRequest $request, Order $order, UpdateOrder $action): RedirectResponse
    {
        $action->handle(
            order: $order,
            customerEmail: $request->customerEmail(),
            totalCents: $request->totalCents(),
            currency: $request->currency(),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Order updated.')]);

        return to_route('admin.orders.index');
    }

    #[Authorize('delete', 'order')]
    public function destroy(Order $order, DeleteOrder $action): RedirectResponse
    {
        $action->handle($order);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Order deleted.')]);

        return to_route('admin.orders.index');
    }
}
