<?php

declare(strict_types=1);

namespace Modules\Shop\Http\Controllers\Api\V1;

use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Modules\Shop\Http\Resources\V1\OrderResource;
use Modules\Shop\Infrastructure\Models\Order;
use Modules\Shop\Queries\OrdersIndexQuery;

/**
 * Orders over the API: the same filters, sorts and pages as the admin list
 * (filter[search], filter[status], sort, page, per_page).
 */
final readonly class OrderController
{
    #[Authorize('viewAny', Order::class)]
    public function index(OrdersIndexQuery $query): AnonymousResourceCollection
    {
        return OrderResource::collection($query->paginate());
    }

    #[Authorize('viewAny', Order::class)]
    public function show(Order $order): OrderResource
    {
        return new OrderResource($order);
    }
}
