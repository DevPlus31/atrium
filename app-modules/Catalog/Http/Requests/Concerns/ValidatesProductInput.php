<?php

declare(strict_types=1);

namespace Modules\Catalog\Http\Requests\Concerns;

use App\Domain\ValueObjects\Money;
use Illuminate\Validation\Rule;
use Modules\Catalog\Domain\ValueObjects\Sku;
use Modules\Catalog\Infrastructure\Models\Product;

/**
 * The product form shared by create and update: rules, normalisation and
 * typed accessors. On update the routed product is ignored by the unique SKU
 * rule; on create there is none.
 */
trait ValidatesProductInput
{
    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        $product = $this->route('product');

        return [
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'regex:'.Sku::PATTERN, Rule::unique(Product::class, 'sku')->ignore($product instanceof Product ? $product : null)],
            'price_cents' => ['required', 'integer', 'min:0'],
            'currency' => ['required', 'string', 'regex:'.Money::CURRENCY_PATTERN],
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

    /**
     * Normalise the SKU and currency the way their value objects do, so the
     * format and uniqueness rules judge the value that will actually be stored.
     */
    protected function prepareForValidation(): void
    {
        foreach (['sku', 'currency'] as $field) {
            $value = $this->input($field);

            if (is_string($value)) {
                $this->merge([$field => mb_strtoupper(mb_trim($value))]);
            }
        }
    }
}
