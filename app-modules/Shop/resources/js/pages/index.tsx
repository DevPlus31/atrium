import { Head, router } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { useState } from 'react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import {
    DataTable,
    DataTableBulkDelete,
    DataTableCreateButton,
    DataTableDeleteDialog,
    DataTableFacetedFilter,
    DataTableToolbar,
} from '@/components/data-table';
import { useBreadcrumbs } from '@/hooks/use-breadcrumbs';
import { useConfirmDialog } from '@/hooks/use-confirm-dialog';
import { useFormatters } from '@/hooks/use-formatters';
import { useResourceTable } from '@/hooks/use-resource-table';
import {
    bulkDestroy,
    create,
    destroy,
    index,
    transition,
} from '@/routes/admin/orders';
import type { Paginated } from '@/types/admin';
import type { OrderRow, OrderStatus } from '../components/order-columns';
import { buildOrderColumns, orderStatuses } from '../components/order-columns';

type OrdersIndexProps = {
    orders: Paginated<OrderRow>;
    statuses: OrderStatus[];
    can: { create: boolean };
};

export default function OrdersIndex({
    orders,
    statuses,
    can,
}: OrdersIndexProps) {
    const { t, tChoice } = useLaravelReactI18n();
    const format = useFormatters();
    const { tableState, deleteDialog, selection, bulkDelete } =
        useResourceTable({
            key: 'orders',
            rows: orders.data,
            destroyUrl: (row) => destroy.url(row.id),
            bulkDestroyUrl: bulkDestroy.url(),
        });
    // Cancelling is irreversible, so it asks first; other moves apply at once.
    const cancelDialog = useConfirmDialog<OrderRow>((order, options) =>
        router.post(transition.url(order.id), { status: 'cancelled' }, options),
    );

    useBreadcrumbs({ title: t('Orders'), href: index() });

    // Clicks are discrete events: React commits this before the next click.
    const [moving, setMoving] = useState(false);

    const moveOrder = (order: OrderRow, status: OrderStatus) => {
        if (moving) {
            return;
        }

        setMoving(true);
        router.post(
            transition.url(order.id),
            { status },
            { preserveScroll: true, onFinish: () => setMoving(false) },
        );
    };

    const columns = buildOrderColumns(
        t,
        format,
        (order, status) =>
            status === 'cancelled'
                ? cancelDialog.request(order)
                : moveOrder(order, status),
        deleteDialog.request,
    );

    return (
        <>
            <Head title={t('Orders')} />
            <DataTableToolbar
                tableState={tableState}
                searchPlaceholder={t('Search by number or email...')}
                actions={
                    can.create && (
                        <DataTableCreateButton
                            href={create()}
                            label={t('Create order')}
                        />
                    )
                }
            >
                <DataTableFacetedFilter
                    tableState={tableState}
                    field="status"
                    title={t('Status')}
                    options={statuses.map((status) => ({
                        label: t(orderStatuses[status].label),
                        value: status,
                    }))}
                />
            </DataTableToolbar>
            <DataTableBulkDelete
                selection={selection}
                bulkDelete={bulkDelete}
                title={t('Delete selected orders')}
                description={(count) =>
                    tChoice(
                        'This will permanently delete :count order and cannot be undone.|This will permanently delete :count orders and cannot be undone.',
                        count,
                    )
                }
            />
            <DataTable
                selection={selection}
                columns={columns}
                paginated={orders}
                tableState={tableState}
                emptyMessage={t('No orders found.')}
            />
            <DataTableDeleteDialog
                dialog={deleteDialog}
                title={t('Delete order')}
                name={(order) => order.number}
                fallbackName={t('this order')}
            />
            <ConfirmDialog
                {...cancelDialog.dialogProps}
                title={t('Cancel order')}
                description={t(
                    'Cancel :number? Cancelled orders cannot be reopened.',
                    { number: cancelDialog.pending?.number ?? '' },
                )}
                confirmLabel={t('Cancel order')}
                cancelLabel={t('Keep order')}
            />
        </>
    );
}
