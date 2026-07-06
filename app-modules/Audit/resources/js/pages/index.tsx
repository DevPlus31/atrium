import { Head } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import {
    DataTable,
    DataTableFacetedFilter,
    DataTableToolbar,
} from '@/components/data-table';
import { useTableState } from '@/hooks/use-table-state';
import AdminLayout from '@/layouts/admin-layout';
import { index } from '@/routes/admin/audit';
import type { BreadcrumbItem } from '@/types';
import type { Paginated } from '@/types/admin';
import type { ActivityRow } from '../components/activity-columns';
import { buildActivityColumns } from '../components/activity-columns';

type AuditIndexProps = {
    activities: Paginated<ActivityRow>;
    logNames: string[];
    events: string[];
};

export default function AuditIndex({
    activities,
    logNames,
    events,
}: AuditIndexProps) {
    const { t } = useLaravelReactI18n();
    const tableState = useTableState('activities');

    const breadcrumbs: BreadcrumbItem[] = [
        { title: t('Audit log'), href: index() },
    ];

    const columns = buildActivityColumns(t);

    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title={t('Audit log')} />
            <DataTableToolbar
                tableState={tableState}
                searchPlaceholder={t('Search audit log...')}
            >
                <DataTableFacetedFilter
                    tableState={tableState}
                    field="log_name"
                    title={t('Log')}
                    options={logNames.map((logName) => ({
                        label: logName,
                        value: logName,
                    }))}
                />
                <DataTableFacetedFilter
                    tableState={tableState}
                    field="event"
                    title={t('Event')}
                    options={events.map((event) => ({
                        label: event,
                        value: event,
                    }))}
                />
            </DataTableToolbar>
            <DataTable
                columns={columns}
                paginated={activities}
                tableState={tableState}
                emptyMessage={t('No activity found.')}
            />
        </AdminLayout>
    );
}
