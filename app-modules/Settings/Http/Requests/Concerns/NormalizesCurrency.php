<?php

declare(strict_types=1);

namespace Modules\Settings\Http\Requests\Concerns;

use App\Domain\ValueObjects\Money;
use App\Modules\Concerns\ReadsValidatedInput;

/**
 * Uppercase and trim the currency the way Money stores it, so the rules
 * judge the value that will be saved.
 */
trait NormalizesCurrency
{
    use ReadsValidatedInput;

    protected function prepareForValidation(): void
    {
        $this->normalizeInput('currency', Money::normalizeCurrency(...));
    }
}
