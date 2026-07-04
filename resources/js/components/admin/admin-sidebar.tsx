import { Link } from '@inertiajs/react';
import { Search } from 'lucide-react';
import { groupNavItems, resolveNavIcon } from '@/components/admin/nav';
import AppLogo from '@/components/app-logo';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/hooks/use-current-url';
import type { LayoutConfig, NavItem } from '@/types/admin';

type AdminSidebarProps = {
    nav: NavItem[];
    side: 'left' | 'right';
    variant: LayoutConfig['sidebar_variant'];
    collapsible: LayoutConfig['sidebar_collapsible'];
    onOpenCommandPalette: () => void;
};

export function AdminSidebar({
    nav,
    side,
    variant,
    collapsible,
    onOpenCommandPalette,
}: AdminSidebarProps) {
    const { isCurrentUrl } = useCurrentUrl();
    const groups = groupNavItems(nav);
    const homeItem = nav.at(0);

    return (
        <Sidebar side={side} variant={variant} collapsible={collapsible}>
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            {homeItem ? (
                                <Link href={homeItem.href} prefetch>
                                    <AppLogo />
                                </Link>
                            ) : (
                                <AppLogo />
                            )}
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                    <SidebarMenuItem>
                        <SidebarMenuButton
                            onClick={onOpenCommandPalette}
                            tooltip={{ children: 'Search' }}
                        >
                            <Search />
                            <span>Search</span>
                            <kbd className="pointer-events-none ms-auto text-xs text-muted-foreground select-none group-data-[collapsible=icon]:hidden">
                                Ctrl K
                            </kbd>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                {groups.map((group) => (
                    <SidebarGroup
                        key={group.label ?? '__top-level'}
                        className="px-2 py-0"
                    >
                        {group.label !== null && (
                            <SidebarGroupLabel>{group.label}</SidebarGroupLabel>
                        )}
                        <SidebarMenu>
                            {group.items.map((item) => {
                                const ItemIcon = resolveNavIcon(item.icon);

                                return (
                                    <SidebarMenuItem key={item.routeName}>
                                        <SidebarMenuButton
                                            asChild
                                            isActive={isCurrentUrl(item.href)}
                                            tooltip={{ children: item.label }}
                                        >
                                            {item.external ? (
                                                <a href={item.href}>
                                                    <ItemIcon />
                                                    <span>{item.label}</span>
                                                </a>
                                            ) : (
                                                <Link href={item.href} prefetch>
                                                    <ItemIcon />
                                                    <span>{item.label}</span>
                                                </Link>
                                            )}
                                        </SidebarMenuButton>
                                    </SidebarMenuItem>
                                );
                            })}
                        </SidebarMenu>
                    </SidebarGroup>
                ))}
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
