<?php

declare(strict_types=1);

namespace Modules\Shop\Http\Controllers;

use App\Modules\Concerns\DeletesSelection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Modules\Shop\Actions\DeleteOrder;
use Modules\Shop\Http\Requests\DeleteOrdersRequest;
use Modules\Shop\Infrastructure\Models\Order;

final readonly class DeleteOrdersController
{
    use DeletesSelection;

    /**
     * Delete the orders selected in the table; rows the user may not delete
     * are left alone and counted in the message.
     */
    #[Authorize('viewAny', Order::class)]
    public function __invoke(DeleteOrdersRequest $request, DeleteOrder $deleteOrder): RedirectResponse
    {
        $this->deleteSelection(
            $request,
            $request->orders(),
            $deleteOrder->handle(...),
            static fn (int $count): string => trans_choice(':count order deleted.|:count orders deleted.', $count),
        );

        return to_route('admin.orders.index');
    }
}
