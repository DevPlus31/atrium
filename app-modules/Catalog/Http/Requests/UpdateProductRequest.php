<?php

declare(strict_types=1);

namespace Modules\Catalog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Catalog\Http\Requests\Concerns\ValidatesProductInput;

final class UpdateProductRequest extends FormRequest
{
    use ValidatesProductInput;

    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('product')) ?? false;
    }
}
