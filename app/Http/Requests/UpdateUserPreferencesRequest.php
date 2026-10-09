<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Actions\ResolveUserPreferences;
use App\Enums\Appearance;
use App\Enums\ThemePreset;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Config;
use Illuminate\Validation\Rule;

final class UpdateUserPreferencesRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $layoutKeys = array_keys(ResolveUserPreferences::LAYOUT_OPTIONS);

        return [
            'appearance' => ['sometimes', 'required', 'string', Rule::enum(Appearance::class)],
            'theme' => ['sometimes', 'required', 'string', Rule::enum(ThemePreset::class)],
            'locale' => ['sometimes', 'required', 'string', Rule::in(array_keys(Config::array('app.available_locales')))],
            // null switches back to the browser's timezone.
            'timezone' => ['sometimes', 'nullable', 'string', 'timezone:all_with_bc'],
            'layout' => ['sometimes', 'required', 'array:'.implode(',', $layoutKeys)],
            ...array_combine(
                array_map(static fn (string $key): string => 'layout.'.$key, $layoutKeys),
                array_map(
                    static fn (string $enum): array => ['sometimes', 'required', 'string', Rule::enum($enum)],
                    ResolveUserPreferences::LAYOUT_OPTIONS,
                ),
            ),
        ];
    }
}
