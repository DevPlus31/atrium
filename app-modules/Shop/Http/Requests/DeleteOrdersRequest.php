<?php

declare(strict_types=1);

namespace Modules\Shop\Http\Requests;

use App\Modules\Concerns\ValidatesBulkSelection;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Http\FormRequest;
use Modules\Shop\Infrastructure\Models\Order;

final class DeleteOrdersRequest extends FormRequest
{
    use ValidatesBulkSelection;

    /**
     * The selected orders the user may delete.
     *
     * @return Collection<int, Order>
     */
    public function orders(): Collection
    {
        return $this->permitted(Order::query(), 'delete');
    }
}
