import { useLaravelReactI18n } from 'laravel-react-i18n';
import { Settings2 } from 'lucide-react';
import {
    appearanceOptions,
    navPlacementOptions,
    themePresetOptions,
} from '@/components/admin/theme-options';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuLabel,
    DropdownMenuRadioGroup,
    DropdownMenuRadioItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useThemePreference } from '@/hooks/use-appearance';
import { useLocalePreference } from '@/hooks/use-locale';

/**
 * The display settings menu required by docs/specs/theming.md: appearance,
 * theme preset, navigation placement, and language toggles, available from
 * the shell header in every layout variant.
 */
export function ThemeSettingsMenu() {
    const {
        appearance,
        updateAppearance,
        theme,
        updateTheme,
        layout,
        updateLayout,
    } = useThemePreference();
    const { locale, locales, updateLocale } = useLocalePreference();
    const { t } = useLaravelReactI18n();

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    variant="ghost"
                    size="icon"
                    className="text-muted-foreground"
                >
                    <Settings2 />
                    <span className="sr-only">{t('Display settings')}</span>
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-56">
                <DropdownMenuLabel>{t('Appearance')}</DropdownMenuLabel>
                <DropdownMenuRadioGroup value={appearance}>
                    {appearanceOptions.map((option) => (
                        <DropdownMenuRadioItem
                            key={option.value}
                            value={option.value}
                            onSelect={() => updateAppearance(option.value)}
                        >
                            <option.icon className="me-2 size-4" />
                            {t(option.label)}
                        </DropdownMenuRadioItem>
                    ))}
                </DropdownMenuRadioGroup>
                <DropdownMenuSeparator />
                <DropdownMenuLabel>{t('Theme')}</DropdownMenuLabel>
                <DropdownMenuRadioGroup value={theme}>
                    {themePresetOptions.map((option) => (
                        <DropdownMenuRadioItem
                            key={option.value}
                            value={option.value}
                            onSelect={() => updateTheme(option.value)}
                        >
                            {t(option.label)}
                        </DropdownMenuRadioItem>
                    ))}
                </DropdownMenuRadioGroup>
                <DropdownMenuSeparator />
                <DropdownMenuLabel>{t('Navigation')}</DropdownMenuLabel>
                <DropdownMenuRadioGroup value={layout.nav_placement}>
                    {navPlacementOptions.map((option) => (
                        <DropdownMenuRadioItem
                            key={option.value}
                            value={option.value}
                            onSelect={() =>
                                updateLayout({ nav_placement: option.value })
                            }
                        >
                            <option.icon className="me-2 size-4" />
                            {t(option.label)}
                        </DropdownMenuRadioItem>
                    ))}
                </DropdownMenuRadioGroup>
                <DropdownMenuSeparator />
                <DropdownMenuLabel>{t('Language')}</DropdownMenuLabel>
                <DropdownMenuRadioGroup value={locale}>
                    {Object.entries(locales).map(([code, label]) => (
                        <DropdownMenuRadioItem
                            key={code}
                            value={code}
                            onSelect={() => updateLocale(code)}
                        >
                            {label}
                        </DropdownMenuRadioItem>
                    ))}
                </DropdownMenuRadioGroup>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
