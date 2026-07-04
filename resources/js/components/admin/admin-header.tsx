import { ThemeSettingsMenu } from '@/components/admin/theme-settings-menu';
import { Breadcrumbs } from '@/components/breadcrumbs';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { cn } from '@/lib/utils';
import type { BreadcrumbItem } from '@/types';

type AdminHeaderProps = {
    breadcrumbs?: BreadcrumbItem[];
    className?: string;
};

export function AdminHeader({ breadcrumbs = [], className }: AdminHeaderProps) {
    return (
        <header
            className={cn(
                'flex h-16 shrink-0 items-center gap-2 border-b border-sidebar-border/50 bg-background px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4',
                className,
            )}
        >
            <div className="flex items-center gap-2">
                <SidebarTrigger className="-ms-1" />
                <Breadcrumbs breadcrumbs={breadcrumbs} />
            </div>
            <div className="ms-auto flex items-center">
                <ThemeSettingsMenu />
            </div>
        </header>
    );
}
