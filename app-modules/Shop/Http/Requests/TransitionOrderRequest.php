<?php

declare(strict_types=1);

namespace Modules\Shop\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Shop\Domain\Enums\OrderStatus;
use Modules\Shop\Infrastructure\Models\Order;

final class TransitionOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('transition', $this->routedOrder()) ?? false;
    }

    /**
     * Only the moves the order's workflow allows from its current status.
     *
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(array_map(
                static fn (OrderStatus $status): string => $status->value,
                $this->routedOrder()->status->transitions(),
            ))],
        ];
    }

    public function status(): OrderStatus
    {
        return OrderStatus::from($this->string('status')->value());
    }

    private function routedOrder(): Order
    {
        $order = $this->route('order');

        assert($order instanceof Order);

        return $order;
    }
}
