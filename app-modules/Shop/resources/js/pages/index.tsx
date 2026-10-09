import { Head, Link, router } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { Plus } from 'lucide-react';
import { useState } from 'react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import {
    DataTable,
    DataTableBulkActions,
    DataTableFacetedFilter,
    DataTableToolbar,
} from '@/components/data-table';
import { Button } from '@/components/ui/button';
import { useBreadcrumbs } from '@/hooks/use-breadcrumbs';
import { useBulkDelete } from '@/hooks/use-bulk-delete';
import { useDeleteDialog } from '@/hooks/use-delete-dialog';
import { useFormatters } from '@/hooks/use-formatters';
import { useRowSelection } from '@/hooks/use-row-selection';
import { useTableState } from '@/hooks/use-table-state';
import {
    bulkDestroy,
    create,
    destroy,
    index,
    transition,
} from '@/routes/admin/orders';
import type { BreadcrumbItem } from '@/types';
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
    const tableState = useTableState('orders');
    const deleteDialog = useDeleteDialog<OrderRow>((row) =>
        destroy.url(row.id),
    );
    const selection = useRowSelection(
        orders.data,
        (row) => row.id,
        (row) => row.can.delete,
    );
    const bulkDelete = useBulkDelete(bulkDestroy.url(), selection);
    const [pendingCancel, setPendingCancel] = useState<OrderRow | null>(null);
    const [cancelling, setCancelling] = useState(false);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: t('Orders'), href: index() },
    ];
    useBreadcrumbs(breadcrumbs);

    // Clicks are discrete events: React commits this before the next click.
    const [moving, setMoving] = useState(false);

    const moveOrder = (
        order: OrderRow,
        status: OrderStatus,
        onFinish?: () => void,
    ) => {
        if (moving) {
            return;
        }

        setMoving(true);

        router.post(
            transition.url(order.id),
            { status },
            {
                preserveScroll: true,
                onFinish: () => {
                    setMoving(false);
                    onFinish?.();
                },
            },
        );
    };

    // Cancelling is irreversible, so it asks first; other moves apply at once.
    const columns = buildOrderColumns(
        t,
        format,
        (order, status) =>
            status === 'cancelled'
                ? setPendingCancel(order)
                : moveOrder(order, status),
        deleteDialog.request,
    );

    const confirmCancel = () => {
        if (pendingCancel === null || cancelling) {
            return;
        }

        setCancelling(true);
        moveOrder(pendingCancel, 'cancelled', () => {
            setCancelling(false);
            setPendingCancel(null);
        });
    };

    return (
        <>
            <Head title={t('Orders')} />
            <DataTableToolbar
                tableState={tableState}
                searchPlaceholder={t('Search by number or email...')}
                actions={
                    can.create && (
                        <Button size="sm" asChild>
                            <Link href={create()}>
                                <Plus className="size-4" />
                                {t('Create order')}
                            </Link>
                        </Button>
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
            <DataTableBulkActions selection={selection}>
                <Button
                    variant="destructive"
                    size="sm"
                    onClick={bulkDelete.request}
                    data-test="bulk-delete"
                >
                    {t('Delete selected')}
                </Button>
            </DataTableBulkActions>
            <DataTable
                selection={selection}
                columns={columns}
                paginated={orders}
                tableState={tableState}
                emptyMessage={t('No orders found.')}
            />
            <ConfirmDialog
                {...bulkDelete.dialogProps}
                title={t('Delete selected orders')}
                description={tChoice(
                    'This will permanently delete :count order and cannot be undone.|This will permanently delete :count orders and cannot be undone.',
                    selection.selectedIds.length,
                )}
                confirmLabel={t('Delete')}
            />
            <ConfirmDialog
                {...deleteDialog.dialogProps}
                title={t('Delete order')}
                description={t(
                    'This will permanently delete :name and cannot be undone.',
                    { name: deleteDialog.pending?.number ?? t('this order') },
                )}
                confirmLabel={t('Delete')}
            />
            <ConfirmDialog
                open={pendingCancel !== null}
                onOpenChange={(open) => {
                    if (!open && !cancelling) {
                        setPendingCancel(null);
                    }
                }}
                title={t('Cancel order')}
                description={t(
                    'Cancel :number? Cancelled orders cannot be reopened.',
                    { number: pendingCancel?.number ?? '' },
                )}
                confirmLabel={t('Cancel order')}
                cancelLabel={t('Keep order')}
                processing={cancelling}
                onConfirm={confirmCancel}
            />
        </>
    );
}
