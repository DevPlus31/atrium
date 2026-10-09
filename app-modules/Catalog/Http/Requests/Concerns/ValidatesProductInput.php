<?php

declare(strict_types=1);

namespace Modules\Catalog\Http\Requests\Concerns;

use App\Domain\ValueObjects\Money;
use App\Modules\Concerns\ReadsValidatedInput;
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
    use ReadsValidatedInput;

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
            'currency' => ['required', ...Money::currencyRules()],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function name(): string
    {
        return $this->validatedString('name');
    }

    public function sku(): string
    {
        return $this->validatedString('sku');
    }

    public function priceCents(): int
    {
        return $this->integer('price_cents');
    }

    public function currency(): string
    {
        return $this->validatedString('currency');
    }

    public function description(): ?string
    {
        return $this->validatedNullableString('description');
    }

    /**
     * Normalise the SKU and currency the way their value objects do, so the
     * format and uniqueness rules judge the value that will actually be stored.
     */
    protected function prepareForValidation(): void
    {
        $this->normalizeInput('sku', Sku::normalize(...));
        $this->normalizeInput('currency', Money::normalizeCurrency(...));
    }
}
