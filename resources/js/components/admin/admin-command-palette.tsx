import { router } from '@inertiajs/react';
import { useEffect } from 'react';
import { groupNavItems, resolveNavIcon } from '@/components/admin/nav';
import {
    CommandDialog,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from '@/components/ui/command';
import type { NavItem } from '@/types/admin';

type AdminCommandPaletteProps = {
    nav: NavItem[];
    open: boolean;
    onOpenChange: (open: boolean) => void;
};

export function AdminCommandPalette({
    nav,
    open,
    onOpenChange,
}: AdminCommandPaletteProps) {
    const groups = groupNavItems(nav);

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

    const navigateTo = (item: NavItem) => {
        onOpenChange(false);

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
            description="Search pages and navigate"
        >
            <CommandInput placeholder="Search pages..." />
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
            </CommandList>
        </CommandDialog>
    );
}
