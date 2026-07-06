<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\Appearance;
use App\Enums\ContentWidth;
use App\Enums\Direction;
use App\Enums\HeaderMode;
use App\Enums\NavPlacement;
use App\Enums\SidebarCollapsible;
use App\Enums\SidebarVariant;
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
        return [
            'appearance' => ['sometimes', 'required', 'string', Rule::enum(Appearance::class)],
            'theme' => ['sometimes', 'required', 'string', Rule::enum(ThemePreset::class)],
            'locale' => ['sometimes', 'required', 'string', Rule::in(array_keys(Config::array('app.available_locales')))],
            'layout' => ['sometimes', 'required', 'array:nav_placement,sidebar_variant,sidebar_collapsible,content_width,header,direction'],
            'layout.nav_placement' => ['sometimes', 'required', 'string', Rule::enum(NavPlacement::class)],
            'layout.sidebar_variant' => ['sometimes', 'required', 'string', Rule::enum(SidebarVariant::class)],
            'layout.sidebar_collapsible' => ['sometimes', 'required', 'string', Rule::enum(SidebarCollapsible::class)],
            'layout.content_width' => ['sometimes', 'required', 'string', Rule::enum(ContentWidth::class)],
            'layout.header' => ['sometimes', 'required', 'string', Rule::enum(HeaderMode::class)],
            'layout.direction' => ['sometimes', 'required', 'string', Rule::enum(Direction::class)],
        ];
    }
}
