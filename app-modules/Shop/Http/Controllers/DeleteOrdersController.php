<?php

declare(strict_types=1);

namespace Modules\Shop\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Inertia\Inertia;
use Modules\Shop\Actions\DeleteOrders;
use Modules\Shop\Http\Requests\DeleteOrdersRequest;
use Modules\Shop\Infrastructure\Models\Order;

final readonly class DeleteOrdersController
{
    /**
     * Delete the orders selected in the table; rows the user may not delete
     * are left alone and counted in the message.
     */
    #[Authorize('viewAny', Order::class)]
    public function __invoke(DeleteOrdersRequest $request, DeleteOrders $action): RedirectResponse
    {
        $orders = $request->orders();

        abort_if($orders->isEmpty(), 403);

        $action->handle($orders);

        $skipped = $request->skipped($orders->count());
        $message = trans_choice(':count order deleted.|:count orders deleted.', $orders->count());

        Inertia::flash('toast', ['type' => 'success', 'message' => $skipped === 0
            ? $message
            : $message.' '.trans_choice(':count could not be deleted.|:count could not be deleted.', $skipped)]);

        return to_route('admin.orders.index');
    }
}
