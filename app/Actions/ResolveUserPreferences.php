<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\Appearance;
use App\Enums\ContentWidth;
use App\Enums\Direction;
use App\Enums\HeaderMode;
use App\Enums\NavPlacement;
use App\Enums\SidebarCollapsible;
use App\Enums\SidebarVariant;
use App\Enums\ThemePreset;
use App\Models\User;
use BackedEnum;
use Illuminate\Http\Request;

final readonly class ResolveUserPreferences
{
    /**
     * The layout contract: enumerated options only, no free-form layout.
     *
     * @var array<string, class-string<BackedEnum>>
     */
    public const array LAYOUT_OPTIONS = [
        'nav_placement' => NavPlacement::class,
        'sidebar_variant' => SidebarVariant::class,
        'sidebar_collapsible' => SidebarCollapsible::class,
        'content_width' => ContentWidth::class,
        'header' => HeaderMode::class,
        'direction' => Direction::class,
    ];

    /**
     * Server-side layout defaults, kept in sync with the frontend
     * `LayoutConfig` defaults (resources/js/hooks/use-theme-preference.tsx).
     *
     * @var array<string, string>
     */
    public const array DEFAULT_LAYOUT = [
        'nav_placement' => 'sidebar-left',
        'sidebar_variant' => 'sidebar',
        'sidebar_collapsible' => 'icon',
        'content_width' => 'fluid',
        'header' => 'sticky',
        'direction' => 'ltr',
    ];

    /**
     * Resolve the effective appearance, theme preset, and layout config for
     * the current request. Database preferences are the source of truth for
     * authenticated users; cookies cover guests; invalid or missing values
     * fall back to the defaults.
     *
     * @return array{appearance: Appearance, theme: ThemePreset, layout: array<string, string>}
     */
    public function handle(Request $request): array
    {
        $user = $request->user();
        $user = $user instanceof User ? $user : null;

        return [
            'appearance' => $user->appearance
                ?? Appearance::tryFrom($this->cookieValue($request, 'appearance'))
                ?? Appearance::System,
            'theme' => $user->theme
                ?? ThemePreset::tryFrom($this->cookieValue($request, 'theme'))
                ?? ThemePreset::Default,
            'layout' => [
                ...self::DEFAULT_LAYOUT,
                ...$this->validLayoutOptions($this->cookieLayout($request)),
                ...$this->validLayoutOptions($user->layout ?? []),
            ],
        ];
    }

    private function cookieValue(Request $request, string $name): string
    {
        $value = $request->cookie($name);

        return is_string($value) ? $value : '';
    }

    /**
     * @return array<array-key, mixed>
     */
    private function cookieLayout(Request $request): array
    {
        $decoded = json_decode($this->cookieValue($request, 'layout'), true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Keep only layout keys whose values are valid enum members.
     *
     * @param  array<array-key, mixed>  $layout
     * @return array<string, string>
     */
    private function validLayoutOptions(array $layout): array
    {
        $valid = [];

        foreach (self::LAYOUT_OPTIONS as $key => $enum) {
            $value = $layout[$key] ?? null;

            if (is_string($value) && $enum::tryFrom($value) instanceof BackedEnum) {
                $valid[$key] = $value;
            }
        }

        return $valid;
    }
}
