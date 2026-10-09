<?php

declare(strict_types=1);

namespace Modules\Catalog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Catalog\Http\Requests\Concerns\ValidatesProductInput;
use Modules\Catalog\Infrastructure\Models\Product;

final class StoreProductRequest extends FormRequest
{
    use ValidatesProductInput;

    public function authorize(): bool
    {
        return $this->user()?->can('create', Product::class) ?? false;
    }
}
