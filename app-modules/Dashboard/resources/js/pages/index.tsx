import { Head } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { useBreadcrumbs } from '@/hooks/use-breadcrumbs';
import { index } from '@/routes/admin/dashboard';
import type { BreadcrumbItem } from '@/types';
import { WidgetGrid } from '../components/widget-grid';

type DashboardIndexProps = {
    widgets: Modules.Dashboard.Data.WidgetDescriptorData[];
};

export default function DashboardIndex({ widgets }: DashboardIndexProps) {
    const { t } = useLaravelReactI18n();

    const breadcrumbs: BreadcrumbItem[] = [
        { title: t('Dashboard'), href: index() },
    ];
    useBreadcrumbs(breadcrumbs);

    return (
        <>
            <Head title={t('Dashboard')} />
            <WidgetGrid widgets={widgets} empty={t('No widgets available.')} />
        </>
    );
}
