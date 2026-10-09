<?php

declare(strict_types=1);

namespace Modules\Shop\Http\Requests\Concerns;

use App\Domain\ValueObjects\Money;
use App\Rules\ValidEmail;

/**
 * Shared input contract of the order create and edit forms. The email and
 * currency are normalised first, so validation judges the stored value.
 */
trait ValidatesOrderInput
{
    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'customer_email' => ['required', 'string', 'max:255', 'email', new ValidEmail],
            'total_cents' => ['required', 'integer', 'min:0'],
            'currency' => ['required', 'string', 'regex:'.Money::CURRENCY_PATTERN],
        ];
    }

    public function customerEmail(): string
    {
        /** @var string $email */
        $email = $this->validated('customer_email');

        return $email;
    }

    public function totalCents(): int
    {
        return $this->integer('total_cents');
    }

    public function currency(): string
    {
        /** @var string $currency */
        $currency = $this->validated('currency');

        return $currency;
    }

    protected function prepareForValidation(): void
    {
        $email = $this->input('customer_email');
        $currency = $this->input('currency');

        $this->merge(array_filter([
            'customer_email' => is_string($email) ? mb_strtolower(mb_trim($email)) : null,
            'currency' => is_string($currency) ? mb_strtoupper(mb_trim($currency)) : null,
        ], is_string(...)));
    }
}
