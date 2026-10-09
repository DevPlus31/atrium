<?php

declare(strict_types=1);

namespace Modules\Shop\Http\Requests\Concerns;

/**
 * Uppercase and trim the currency the way Money stores it, so the format rule
 * judges the value that will be saved.
 */
trait NormalizesDefaultCurrency
{
    protected function prepareForValidation(): void
    {
        $currency = $this->input('default_currency');

        if (is_string($currency)) {
            $this->merge(['default_currency' => mb_strtoupper(mb_trim($currency))]);
        }
    }
}
