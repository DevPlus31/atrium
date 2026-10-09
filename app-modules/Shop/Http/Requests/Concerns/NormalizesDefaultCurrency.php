<?php

declare(strict_types=1);

namespace Modules\Shop\Http\Requests\Concerns;

use App\Domain\ValueObjects\Money;
use App\Modules\Concerns\ReadsValidatedInput;

/**
 * Uppercase and trim the currency the way Money stores it, so the format rule
 * judges the value that will be saved.
 */
trait NormalizesDefaultCurrency
{
    use ReadsValidatedInput;

    protected function prepareForValidation(): void
    {
        $this->normalizeInput('default_currency', Money::normalizeCurrency(...));
    }
}
