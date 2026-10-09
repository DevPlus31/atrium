<?php

declare(strict_types=1);

namespace Modules\Shop\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Inertia\Inertia;
use Modules\Shop\Actions\TransitionOrder;
use Modules\Shop\Http\Requests\TransitionOrderRequest;
use Modules\Shop\Infrastructure\Models\Order;

final readonly class TransitionOrderController
{
    #[Authorize('transition', 'order')]
    public function __invoke(TransitionOrderRequest $request, Order $order, TransitionOrder $action): RedirectResponse
    {
        $action->handle($order, $request->status());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Order :number marked as :status.', [
            'number' => $order->number,
            'status' => __($request->status()->value),
        ])]);

        return to_route('admin.orders.index');
    }
}
