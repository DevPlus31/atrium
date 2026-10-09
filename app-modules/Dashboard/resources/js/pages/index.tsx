import { Head } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { useBreadcrumbs } from '@/hooks/use-breadcrumbs';
import { index } from '@/routes/admin/dashboard';
import { WidgetGrid } from '../components/widget-grid';

type DashboardIndexProps = {
    widgets: Modules.Dashboard.Data.WidgetDescriptorData[];
};

export default function DashboardIndex({ widgets }: DashboardIndexProps) {
    const { t } = useLaravelReactI18n();

    useBreadcrumbs({ title: t('Dashboard'), href: index() });

    return (
        <>
            <Head title={t('Dashboard')} />
            <WidgetGrid widgets={widgets} empty={t('No widgets available.')} />
        </>
    );
}
