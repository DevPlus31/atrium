<?php

declare(strict_types=1);

namespace Modules\Shop\Http\Requests;

use App\Domain\ValueObjects\Money;
use Illuminate\Foundation\Http\FormRequest;
use Modules\Shop\Http\Requests\Concerns\NormalizesDefaultCurrency;

final class UpdateShopSettingsRequest extends FormRequest
{
    use NormalizesDefaultCurrency;

    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'default_currency' => ['required', 'string', 'regex:'.Money::CURRENCY_PATTERN],
        ];
    }

    public function defaultCurrency(): string
    {
        /** @var string $currency */
        $currency = $this->validated('default_currency');

        return $currency;
    }
}
