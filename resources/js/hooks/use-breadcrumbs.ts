import { setLayoutProps } from '@inertiajs/react';
import { useLayoutEffect } from 'react';
import type { BreadcrumbItem } from '@/types';

/**
 * Hand the page's breadcrumbs to the persistent AdminLayout. Keyed on the
 * breadcrumb content, so a fresh array on every render never loops.
 */
export function useBreadcrumbs(breadcrumbs: BreadcrumbItem[]): void {
    const key = JSON.stringify(breadcrumbs);

    useLayoutEffect(() => {
        setLayoutProps({ breadcrumbs: JSON.parse(key) as BreadcrumbItem[] });
    }, [key]);
}
