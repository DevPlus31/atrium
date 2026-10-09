<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

final readonly class ValidEmail implements ValidationRule
{
    /**
     * The whole value (anchored; D keeps `$` from accepting a trailing
     * newline): a dot-atom local part, then one or more domain labels and
     * a letters-only TLD, all lowercase.
     */
    private const string REGEX = '/^[a-z0-9!#$%&*+\/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&*+\/=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z]{2,}$/D';

    /**
     * Every rule an email field needs besides required/nullable and
     * uniqueness: one list, so all email fields accept the same addresses.
     *
     * @return list<mixed>
     */
    public static function rules(): array
    {
        return ['string', 'lowercase', 'max:255', 'email', new self];
    }

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || preg_match(self::REGEX, $value) !== 1) {
            $fail('The :attribute must be a valid email address.');
        }
    }
}
