import { Head } from '@inertiajs/react';
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

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Audit log', href: index() }];

const columns = buildActivityColumns();

export default function AuditIndex({
    activities,
    logNames,
    events,
}: AuditIndexProps) {
    const tableState = useTableState('activities');

    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title="Audit log" />
            <DataTableToolbar
                tableState={tableState}
                searchPlaceholder="Search audit log..."
            >
                <DataTableFacetedFilter
                    tableState={tableState}
                    field="log_name"
                    title="Log"
                    options={logNames.map((logName) => ({
                        label: logName,
                        value: logName,
                    }))}
                />
                <DataTableFacetedFilter
                    tableState={tableState}
                    field="event"
                    title="Event"
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
                emptyMessage="No activity found."
            />
        </AdminLayout>
    );
}
