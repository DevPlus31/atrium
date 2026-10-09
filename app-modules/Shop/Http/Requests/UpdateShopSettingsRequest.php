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
            'default_currency' => ['required', ...Money::currencyRules()],
        ];
    }

    public function defaultCurrency(): string
    {
        return $this->validatedString('default_currency');
    }
}
