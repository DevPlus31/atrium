import { Head } from '@inertiajs/react';
import type { InertiaLinkProps } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { useBreadcrumbs } from '@/hooks/use-breadcrumbs';
import SettingsLayout from '@/layouts/settings/layout';

export type SettingsPageProps = {
    /** The page title: tab title, the screen-reader heading and the breadcrumb. */
    title: string;
    /** This page's URL, for the breadcrumb. */
    href: NonNullable<InertiaLinkProps['href']>;
    children: ReactNode;
};

/** An account settings page: title, breadcrumb and the settings layout. */
export function SettingsPage({ title, href, children }: SettingsPageProps) {
    useBreadcrumbs({ title, href });

    return (
        <>
            <Head title={title} />
            <h1 className="sr-only">{title}</h1>
            <SettingsLayout>{children}</SettingsLayout>
        </>
    );
}
