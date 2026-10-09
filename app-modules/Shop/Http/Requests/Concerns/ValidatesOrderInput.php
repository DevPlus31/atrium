<?php

declare(strict_types=1);

namespace Modules\Shop\Http\Requests\Concerns;

use App\Domain\ValueObjects\Email;
use App\Domain\ValueObjects\Money;
use App\Modules\Concerns\ReadsValidatedInput;
use App\Rules\ValidEmail;

/**
 * Shared input contract of the order create and edit forms. The email and
 * currency are normalised first, so validation judges the stored value.
 */
trait ValidatesOrderInput
{
    use ReadsValidatedInput;

    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'customer_email' => ['required', ...ValidEmail::rules()],
            'total_cents' => ['required', 'integer', 'min:0'],
            'currency' => ['required', ...Money::currencyRules()],
        ];
    }

    public function customerEmail(): string
    {
        return $this->validatedString('customer_email');
    }

    public function totalCents(): int
    {
        return $this->integer('total_cents');
    }

    public function currency(): string
    {
        return $this->validatedString('currency');
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeInput('customer_email', Email::normalize(...));
        $this->normalizeInput('currency', Money::normalizeCurrency(...));
    }
}
