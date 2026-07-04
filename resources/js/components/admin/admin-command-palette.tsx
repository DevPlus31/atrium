import { router } from '@inertiajs/react';
import { PanelLeftClose } from 'lucide-react';
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
import type { NavItem } from '@/types/admin';

type AdminCommandPaletteProps = {
    nav: NavItem[];
    open: boolean;
    onOpenChange: (open: boolean) => void;
    withSidebarToggle?: boolean;
};

function SidebarToggleCommand({ onDone }: { onDone: () => void }) {
    const { toggleSidebar } = useSidebar();

    return (
        <CommandItem
            value="Toggle sidebar collapse"
            onSelect={() => {
                onDone();
                toggleSidebar();
            }}
        >
            <PanelLeftClose />
            <span>Toggle sidebar</span>
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
            title="Command palette"
            description="Search pages and preferences"
        >
            <CommandInput placeholder="Search pages and preferences..." />
            <CommandList>
                <CommandEmpty>No results found.</CommandEmpty>
                {groups.map((group) => (
                    <CommandGroup
                        key={group.label ?? '__top-level'}
                        heading={group.label ?? 'General'}
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
                <CommandGroup heading="Preferences">
                    {appearanceOptions.map((option) => (
                        <CommandItem
                            key={option.value}
                            value={`Appearance ${option.label}`}
                            onSelect={() => {
                                close();
                                updateAppearance(option.value);
                            }}
                        >
                            <option.icon />
                            <span>Appearance: {option.label}</span>
                        </CommandItem>
                    ))}
                    {themePresetOptions.map((option) => (
                        <CommandItem
                            key={option.value}
                            value={`Theme ${option.label}`}
                            onSelect={() => {
                                close();
                                updateTheme(option.value);
                            }}
                        >
                            <span>Theme: {option.label}</span>
                        </CommandItem>
                    ))}
                    {navPlacementOptions.map((option) => (
                        <CommandItem
                            key={option.value}
                            value={`Navigation ${option.label}`}
                            onSelect={() => {
                                close();
                                updateLayout({ nav_placement: option.value });
                            }}
                        >
                            <option.icon />
                            <span>Navigation: {option.label}</span>
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
