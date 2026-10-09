<?php

declare(strict_types=1);

namespace App\Modules\Concerns;

use Closure;

/**
 * For FormRequests: typed reads of validated fields, and the normalisation of
 * input before validation, so the rules judge the value that will be stored.
 * Each getter is only called for fields the request's rules validate.
 */
trait ReadsValidatedInput
{
    private function validatedString(string $key): string
    {
        /** @var string $value */
        $value = $this->validated($key);

        return $value;
    }

    private function validatedNullableString(string $key): ?string
    {
        /** @var string|null $value */
        $value = $this->validated($key);

        return $value;
    }

    /**
     * Replace a string input with its normalised form (non-strings are left
     * for the rules to reject).
     *
     * @param  Closure(string): string  $normalize
     */
    private function normalizeInput(string $key, Closure $normalize): void
    {
        $value = $this->input($key);

        if (is_string($value)) {
            $this->merge([$key => $normalize($value)]);
        }
    }
}
