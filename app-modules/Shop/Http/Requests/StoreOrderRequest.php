<?php

declare(strict_types=1);

namespace Modules\Shop\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Shop\Http\Requests\Concerns\ValidatesOrderInput;
use Modules\Shop\Infrastructure\Models\Order;

final class StoreOrderRequest extends FormRequest
{
    use ValidatesOrderInput;

    public function authorize(): bool
    {
        return $this->user()?->can('create', Order::class) ?? false;
    }
}
