import { router } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { Languages, PanelLeftClose } from 'lucide-react';
import { useEffect } from 'react';
import { groupNavItems, resolveNavIcon } from '@/components/admin/nav';
import {
    appearanceOptions,
    navPlacementOptions,
    themePresetOptions,
} from '@/components/admin/theme-options';
import {
    CommandDialog,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
    CommandSeparator,
} from '@/components/ui/command';
import { useSidebar } from '@/components/ui/sidebar';
import { useThemePreference } from '@/hooks/use-appearance';
import { useLocalePreference } from '@/hooks/use-locale';
import type { NavItem } from '@/types/admin';

type AdminCommandPaletteProps = {
    nav: NavItem[];
    open: boolean;
    onOpenChange: (open: boolean) => void;
    withSidebarToggle?: boolean;
};

function SidebarToggleCommand({ onDone }: { onDone: () => void }) {
    const { toggleSidebar } = useSidebar();
    const { t } = useLaravelReactI18n();

    return (
        <CommandItem
            value={t('Toggle sidebar collapse')}
            onSelect={() => {
                onDone();
                toggleSidebar();
            }}
        >
            <PanelLeftClose />
            <span>{t('Toggle sidebar')}</span>
        </CommandItem>
    );
}

export function AdminCommandPalette({
    nav,
    open,
    onOpenChange,
    withSidebarToggle = false,
}: AdminCommandPaletteProps) {
    const groups = groupNavItems(nav);
    const { updateAppearance, updateTheme, updateLayout } =
        useThemePreference();
    const { locales, updateLocale } = useLocalePreference();
    const { t } = useLaravelReactI18n();

    useEffect(() => {
        const handleKeyDown = (event: KeyboardEvent) => {
            if (
                event.key.toLowerCase() === 'k' &&
                (event.metaKey || event.ctrlKey)
            ) {
                event.preventDefault();
                onOpenChange(!open);
            }
        };

        window.addEventListener('keydown', handleKeyDown);

        return () => window.removeEventListener('keydown', handleKeyDown);
    }, [open, onOpenChange]);

    const close = () => onOpenChange(false);

    const navigateTo = (item: NavItem) => {
        close();

        if (item.external) {
            window.location.assign(item.href);

            return;
        }

        router.visit(item.href);
    };

    return (
        <CommandDialog
            open={open}
            onOpenChange={onOpenChange}
            title={t('Command palette')}
            description={t('Search pages and preferences')}
        >
            <CommandInput placeholder={t('Search pages and preferences...')} />
            <CommandList>
                <CommandEmpty>{t('No results found.')}</CommandEmpty>
                {groups.map((group) => (
                    <CommandGroup
                        key={group.label ?? '__top-level'}
                        heading={group.label ?? t('General')}
                    >
                        {group.items.map((item) => {
                            const ItemIcon = resolveNavIcon(item.icon);

                            return (
                                <CommandItem
                                    key={item.routeName}
                                    value={`${item.label} ${item.routeName}`}
                                    onSelect={() => navigateTo(item)}
                                >
                                    <ItemIcon />
                                    <span>{item.label}</span>
                                </CommandItem>
                            );
                        })}
                    </CommandGroup>
                ))}
                <CommandSeparator />
                <CommandGroup heading={t('Preferences')}>
                    {appearanceOptions.map((option) => (
                        <CommandItem
                            key={option.value}
                            value={`${t('Appearance')} ${t(option.label)}`}
                            onSelect={() => {
                                close();
                                updateAppearance(option.value);
                            }}
                        >
                            <option.icon />
                            <span>
                                {t('Appearance')}: {t(option.label)}
                            </span>
                        </CommandItem>
                    ))}
                    {themePresetOptions.map((option) => (
                        <CommandItem
                            key={option.value}
                            value={`${t('Theme')} ${t(option.label)}`}
                            onSelect={() => {
                                close();
                                updateTheme(option.value);
                            }}
                        >
                            <span>
                                {t('Theme')}: {t(option.label)}
                            </span>
                        </CommandItem>
                    ))}
                    {navPlacementOptions.map((option) => (
                        <CommandItem
                            key={option.value}
                            value={`${t('Navigation')} ${t(option.label)}`}
                            onSelect={() => {
                                close();
                                updateLayout({ nav_placement: option.value });
                            }}
                        >
                            <option.icon />
                            <span>
                                {t('Navigation')}: {t(option.label)}
                            </span>
                        </CommandItem>
                    ))}
                    {Object.entries(locales).map(([code, label]) => (
                        <CommandItem
                            key={code}
                            value={`${t('Language')} ${label}`}
                            onSelect={() => {
                                close();
                                updateLocale(code);
                            }}
                        >
                            <Languages />
                            <span>
                                {t('Language')}: {label}
                            </span>
                        </CommandItem>
                    ))}
                    {withSidebarToggle && (
                        <SidebarToggleCommand onDone={close} />
                    )}
                </CommandGroup>
            </CommandList>
        </CommandDialog>
    );
}
