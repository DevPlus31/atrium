import { Head, usePage } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { useBreadcrumbs } from '@/hooks/use-breadcrumbs';
import { dashboard } from '@/routes/member';
import type { BreadcrumbItem } from '@/types';
import { WidgetGrid } from '../components/widget-grid';

type DashboardHomeProps = {
    widgets: Modules.Dashboard.Data.WidgetDescriptorData[];
};

export default function DashboardHome({ widgets }: DashboardHomeProps) {
    const { t } = useLaravelReactI18n();
    const { auth } = usePage().props;
    const firstName = auth.user?.name.split(' ')[0] ?? '';

    const breadcrumbs: BreadcrumbItem[] = [
        { title: t('Home'), href: dashboard() },
    ];
    useBreadcrumbs(breadcrumbs);

    return (
        <>
            <Head title={t('Home')} />
            <header className="mb-8 max-w-2xl">
                <h1 className="font-display text-4xl leading-tight tracking-tight text-balance md:text-5xl">
                    {t('Welcome, :name', { name: firstName })}
                </h1>
                <p className="mt-2 text-muted-foreground">
                    {t('Here is where your account stands.')}
                </p>
            </header>
            <WidgetGrid widgets={widgets} empty={t('Nothing here yet.')} />
        </>
    );
}
