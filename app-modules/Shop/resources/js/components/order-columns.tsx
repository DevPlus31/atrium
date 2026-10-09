import {
    actionsColumn,
    dateColumn,
    sortableColumn,
    DeleteMenuItem,
    EditMenuItem,
    type DataTableColumn,
} from '@/components/data-table';
import { Badge } from '@/components/ui/badge';
import {
    DropdownMenuItem,
    DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
import type { Formatters } from '@/lib/format';
import { edit } from '@/routes/admin/orders';
import type { Translator } from '@/types/ui';

export type OrderRow = Modules.Shop.Data.OrderData;
export type OrderStatus = Modules.Shop.Domain.Enums.OrderStatus;

/**
 * Display label and badge style per status, exhaustive over the enum, plus
 * the menu label for moving an order there (no order goes back to pending).
 */
export const orderStatuses: Record<
    OrderStatus,
    {
        label: string;
        action?: string;
        variant: 'default' | 'secondary' | 'outline' | 'destructive';
    }
> = {
    pending: { label: 'Pending', variant: 'outline' },
    paid: { label: 'Paid', action: 'Mark as paid', variant: 'secondary' },
    shipped: {
        label: 'Shipped',
        action: 'Mark as shipped',
        variant: 'default',
    },
    cancelled: {
        label: 'Cancelled',
        action: 'Cancel order',
        variant: 'destructive',
    },
};

export function buildOrderColumns(
    t: Translator,
    format: Formatters,
    onTransition: (order: OrderRow, status: OrderStatus) => void,
    onDelete: (order: OrderRow) => void,
): DataTableColumn<OrderRow>[] {
    return [
        sortableColumn<OrderRow>({
            id: 'number',
            title: t('Order'),
            cell: (row) => (
                <span className="font-mono text-xs font-medium">
                    {row.number}
                </span>
            ),
        }),
        sortableColumn<OrderRow>({
            id: 'customer_email',
            title: t('Customer'),
        }),
        sortableColumn<OrderRow>({
            id: 'status',
            title: t('Status'),
            cell: (row) => {
                const status = orderStatuses[row.status];

                return (
                    <Badge variant={status.variant}>{t(status.label)}</Badge>
                );
            },
        }),
        sortableColumn<OrderRow>({
            id: 'total_cents',
            title: t('Total'),
            cell: (row) => (
                <span className="tabular-nums">
                    {format.money(row.total_cents, row.currency)}
                </span>
            ),
        }),
        dateColumn<OrderRow>({
            id: 'created_at',
            title: t('Placed'),
            value: (order) => order.created_at,
            format: format.date,
        }),
        actionsColumn<OrderRow>({
            label: t('Actions'),
            visible: (order) =>
                order.can.update ||
                order.can.delete ||
                order.transitions.length > 0,
            items: (order) => (
                <>
                    {order.can.update && <EditMenuItem href={edit(order.id)} />}
                    {order.transitions.map((status) => (
                        <DropdownMenuItem
                            key={status}
                            variant={
                                status === 'cancelled'
                                    ? 'destructive'
                                    : 'default'
                            }
                            onSelect={() => onTransition(order, status)}
                        >
                            {t(
                                orderStatuses[status].action ??
                                    orderStatuses[status].label,
                            )}
                        </DropdownMenuItem>
                    ))}
                    {order.can.delete && (
                        <>
                            <DropdownMenuSeparator />
                            <DeleteMenuItem onSelect={() => onDelete(order)} />
                        </>
                    )}
                </>
            ),
        }),
    ];
}
