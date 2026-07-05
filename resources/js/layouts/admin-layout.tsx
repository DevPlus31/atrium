import { usePage } from '@inertiajs/react';
import { DirectionProvider } from '@radix-ui/react-direction';
import type { ReactNode } from 'react';
import { useEffect, useState } from 'react';
import { AdminCommandPalette } from '@/components/admin/admin-command-palette';
import { AdminHeader } from '@/components/admin/admin-header';
import { AdminSidebar } from '@/components/admin/admin-sidebar';
import { AdminTopbar } from '@/components/admin/admin-topbar';
import { ImpersonationBanner } from '@/components/admin/impersonation-banner';
import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';
import { useSharedFlashToast } from '@/hooks/use-flash-toast';
import { cn } from '@/lib/utils';
import type { BreadcrumbItem } from '@/types';

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
    const { nav, layout } = usePage().props;

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
            withSidebarToggle={layout.nav_placement !== 'topbar'}
        />
    );

    if (layout.nav_placement === 'topbar') {
        return (
            <DirectionProvider dir={layout.direction}>
                <div className="flex min-h-svh w-full flex-col bg-background">
                    <ImpersonationBanner />
                    <AdminTopbar
                        nav={nav}
                        breadcrumbs={breadcrumbs}
                        onOpenCommandPalette={openCommandPalette}
                        className={headerClassName}
                    />
                    <main className={contentClassName}>{children}</main>
                    {commandPalette}
                </div>
            </DirectionProvider>
        );
    }

    return (
        <DirectionProvider dir={layout.direction}>
            <AppShell variant="sidebar">
                <AdminSidebar
                    nav={nav}
                    side={
                        layout.nav_placement === 'sidebar-right'
                            ? 'right'
                            : 'left'
                    }
                    variant={layout.sidebar_variant}
                    collapsible={layout.sidebar_collapsible}
                    onOpenCommandPalette={openCommandPalette}
                />
                <AppContent variant="sidebar" className="overflow-x-hidden">
                    <ImpersonationBanner />
                    <AdminHeader
                        breadcrumbs={breadcrumbs}
                        className={headerClassName}
                    />
                    <div className={contentClassName}>{children}</div>
                </AppContent>
                {commandPalette}
            </AppShell>
        </DirectionProvider>
    );
}
