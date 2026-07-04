import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { useEffect, useState } from 'react';
import { AdminCommandPalette } from '@/components/admin/admin-command-palette';
import { AdminHeader } from '@/components/admin/admin-header';
import { AdminSidebar } from '@/components/admin/admin-sidebar';
import { AdminTopbar } from '@/components/admin/admin-topbar';
import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';
import { useSharedFlashToast } from '@/hooks/use-flash-toast';
import { cn } from '@/lib/utils';
import type { BreadcrumbItem } from '@/types';
import type { LayoutConfig, NavItem } from '@/types/admin';

const defaultLayoutConfig: LayoutConfig = {
    nav_placement: 'sidebar-left',
    sidebar_variant: 'sidebar',
    sidebar_collapsible: 'icon',
    content_width: 'fluid',
    header: 'sticky',
    direction: 'ltr',
};

type AdminLayoutProps = {
    children: ReactNode;
    breadcrumbs?: BreadcrumbItem[];
};

/**
 * Persistent admin layout. The single interpreter of the `layout` shared
 * prop — every other admin component stays layout-agnostic.
 */
export default function AdminLayout({
    children,
    breadcrumbs = [],
}: AdminLayoutProps) {
    const props = usePage().props;
    const nav = (props.nav as NavItem[] | undefined) ?? [];
    const layout: LayoutConfig = {
        ...defaultLayoutConfig,
        ...(props.layout as Partial<LayoutConfig> | undefined),
    };

    const [paletteOpen, setPaletteOpen] = useState(false);

    useSharedFlashToast();

    useEffect(() => {
        document.documentElement.dir = layout.direction;
    }, [layout.direction]);

    const headerClassName =
        layout.header === 'sticky' ? 'sticky top-0 z-40' : undefined;
    const contentClassName = cn(
        'flex w-full flex-1 flex-col gap-4 p-4',
        layout.content_width === 'boxed' && 'mx-auto max-w-7xl',
    );

    const openCommandPalette = () => setPaletteOpen(true);
    const commandPalette = (
        <AdminCommandPalette
            nav={nav}
            open={paletteOpen}
            onOpenChange={setPaletteOpen}
        />
    );

    if (layout.nav_placement === 'topbar') {
        return (
            <div className="flex min-h-svh w-full flex-col bg-background">
                <AdminTopbar
                    nav={nav}
                    breadcrumbs={breadcrumbs}
                    onOpenCommandPalette={openCommandPalette}
                    className={headerClassName}
                />
                <main className={contentClassName}>{children}</main>
                {commandPalette}
            </div>
        );
    }

    return (
        <AppShell variant="sidebar">
            <AdminSidebar
                nav={nav}
                side={
                    layout.nav_placement === 'sidebar-right' ? 'right' : 'left'
                }
                variant={layout.sidebar_variant}
                collapsible={layout.sidebar_collapsible}
                onOpenCommandPalette={openCommandPalette}
            />
            <AppContent variant="sidebar" className="overflow-x-hidden">
                <AdminHeader
                    breadcrumbs={breadcrumbs}
                    className={headerClassName}
                />
                <div className={contentClassName}>{children}</div>
            </AppContent>
            {commandPalette}
        </AppShell>
    );
}
