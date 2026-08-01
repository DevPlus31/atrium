<?php

declare(strict_types=1);

namespace Modules\Catalog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Catalog\Infrastructure\Models\Product;

final class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Product::class) ?? false;
    }

    /**
     * @return array<string, array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:32', Rule::unique(Product::class, 'sku')],
            'price_cents' => ['required', 'integer', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function name(): string
    {
        /** @var string $name */
        $name = $this->validated('name');

        return $name;
    }

    public function sku(): string
    {
        /** @var string $sku */
        $sku = $this->validated('sku');

        return $sku;
    }

    public function priceCents(): int
    {
        return $this->integer('price_cents');
    }

    public function currency(): string
    {
        /** @var string $currency */
        $currency = $this->validated('currency');

        return $currency;
    }

    public function description(): ?string
    {
        /** @var string|null $description */
        $description = $this->validated('description');

        return $description;
    }
}
