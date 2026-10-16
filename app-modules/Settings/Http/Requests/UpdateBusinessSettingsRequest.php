<?php

declare(strict_types=1);

namespace Modules\Settings\Http\Requests;

use App\Domain\ValueObjects\Money;
use App\Enums\DistanceUnit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Settings\Http\Requests\Concerns\NormalizesCurrency;
use ResourceBundle;

final class UpdateBusinessSettingsRequest extends FormRequest
{
    use NormalizesCurrency;

    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'timezone' => ['required', 'string', 'timezone:all'],
            'locale' => ['required', 'string', Rule::in($this->regionalFormats())],
            'currency' => ['required', ...Money::currencyRules(), Rule::in($this->currencies())],
            'distance_unit' => ['required', Rule::enum(DistanceUnit::class)],
        ];
    }

    public function timezone(): string
    {
        return $this->validatedString('timezone');
    }

    public function locale(): string
    {
        return $this->validatedString('locale');
    }

    public function currency(): string
    {
        return $this->validatedString('currency');
    }

    public function distanceUnit(): DistanceUnit
    {
        return DistanceUnit::from($this->validatedString('distance_unit'));
    }

    /**
     * The regional formats the runtime knows, as BCP 47 tags ("en-US").
     *
     * @return list<string>
     */
    private function regionalFormats(): array
    {
        $locales = ResourceBundle::getLocales('');

        return array_values(array_map(
            static fn (string $locale): string => str_replace('_', '-', $locale),
            array_filter(is_array($locales) ? $locales : [], is_string(...)),
        ));
    }

    /**
     * The ISO 4217 codes the runtime knows ("USD").
     *
     * @return list<string>
     */
    private function currencies(): array
    {
        $currencies = ResourceBundle::create('en', 'ICUDATA-curr')?->get('Currencies');

        return $currencies instanceof ResourceBundle ? array_map(strval(...), array_keys(iterator_to_array($currencies))) : [];
    }
}
