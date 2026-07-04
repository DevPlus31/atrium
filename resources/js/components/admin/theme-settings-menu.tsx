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

/**
 * The display settings menu required by docs/specs/theming.md: appearance,
 * theme preset, and navigation placement toggles, available from the shell
 * header in every layout variant.
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

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    variant="ghost"
                    size="icon"
                    className="text-muted-foreground"
                >
                    <Settings2 />
                    <span className="sr-only">Display settings</span>
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-56">
                <DropdownMenuLabel>Appearance</DropdownMenuLabel>
                <DropdownMenuRadioGroup value={appearance}>
                    {appearanceOptions.map((option) => (
                        <DropdownMenuRadioItem
                            key={option.value}
                            value={option.value}
                            onSelect={() => updateAppearance(option.value)}
                        >
                            <option.icon className="me-2 size-4" />
                            {option.label}
                        </DropdownMenuRadioItem>
                    ))}
                </DropdownMenuRadioGroup>
                <DropdownMenuSeparator />
                <DropdownMenuLabel>Theme</DropdownMenuLabel>
                <DropdownMenuRadioGroup value={theme}>
                    {themePresetOptions.map((option) => (
                        <DropdownMenuRadioItem
                            key={option.value}
                            value={option.value}
                            onSelect={() => updateTheme(option.value)}
                        >
                            {option.label}
                        </DropdownMenuRadioItem>
                    ))}
                </DropdownMenuRadioGroup>
                <DropdownMenuSeparator />
                <DropdownMenuLabel>Navigation</DropdownMenuLabel>
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
                            {option.label}
                        </DropdownMenuRadioItem>
                    ))}
                </DropdownMenuRadioGroup>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
