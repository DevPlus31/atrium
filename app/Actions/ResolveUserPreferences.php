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
use App\Modules\Data\LayoutConfigData;
use BackedEnum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

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
     * Server-side layout defaults. The resolved layout always carries every
     * key, so the client never needs its own default values.
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
     * Resolve the effective appearance, theme preset, layout config, and
     * locale for the current request. Database preferences are the source of
     * truth for authenticated users; cookies cover guests; invalid or missing
     * values fall back to the defaults.
     *
     * @return array{appearance: Appearance, theme: ThemePreset, layout: LayoutConfigData, locale: string}
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
            'layout' => LayoutConfigData::from([
                ...self::DEFAULT_LAYOUT,
                ...$this->validLayoutOptions($this->cookieLayout($request)),
                ...$this->validLayoutOptions($user->layout ?? []),
            ]),
            'locale' => $this->validLocale($user?->locale)
                ?? $this->validLocale($this->cookieValue($request, 'locale'))
                ?? Config::string('app.locale'),
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
     * Keep the locale only when it is one of the available locales
     * (config/app.php `available_locales`).
     */
    private function validLocale(?string $locale): ?string
    {
        if ($locale === null) {
            return null;
        }

        return array_key_exists($locale, Config::array('app.available_locales'))
            ? $locale
            : null;
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
