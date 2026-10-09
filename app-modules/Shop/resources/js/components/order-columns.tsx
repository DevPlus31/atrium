import { Link } from '@inertiajs/react';
import {
    DataTableColumnHeader,
    DataTableRowActions,
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
        {
            id: 'number',
            accessorKey: 'number',
            enableSorting: true,
            header: ({ column }) => (
                <DataTableColumnHeader column={column} title={t('Order')} />
            ),
            cell: ({ row }) => (
                <span className="font-mono text-xs font-medium">
                    {row.original.number}
                </span>
            ),
        },
        {
            id: 'customer_email',
            accessorKey: 'customer_email',
            enableSorting: true,
            header: ({ column }) => (
                <DataTableColumnHeader column={column} title={t('Customer')} />
            ),
        },
        {
            id: 'status',
            accessorKey: 'status',
            enableSorting: true,
            header: ({ column }) => (
                <DataTableColumnHeader column={column} title={t('Status')} />
            ),
            cell: ({ row }) => {
                const status = orderStatuses[row.original.status];

                return (
                    <Badge variant={status.variant}>{t(status.label)}</Badge>
                );
            },
        },
        {
            id: 'total_cents',
            accessorKey: 'total_cents',
            enableSorting: true,
            header: ({ column }) => (
                <DataTableColumnHeader column={column} title={t('Total')} />
            ),
            cell: ({ row }) => (
                <span className="tabular-nums">
                    {format.money(
                        row.original.total_cents,
                        row.original.currency,
                    )}
                </span>
            ),
        },
        {
            id: 'created_at',
            accessorKey: 'created_at',
            enableSorting: true,
            header: ({ column }) => (
                <DataTableColumnHeader column={column} title={t('Placed')} />
            ),
            cell: ({ row }) => (
                <span className="text-muted-foreground">
                    {format.date(row.original.created_at)}
                </span>
            ),
        },
        {
            id: 'actions',
            enableSorting: false,
            header: () => <span className="sr-only">{t('Actions')}</span>,
            cell: ({ row }) => {
                const order = row.original;

                if (
                    !order.can.update &&
                    !order.can.delete &&
                    order.transitions.length === 0
                ) {
                    return null;
                }

                return (
                    <div className="flex justify-end">
                        <DataTableRowActions row={row}>
                            {() => (
                                <>
                                    {order.can.update && (
                                        <DropdownMenuItem asChild>
                                            <Link href={edit(order.id)}>
                                                {t('Edit')}
                                            </Link>
                                        </DropdownMenuItem>
                                    )}
                                    {order.transitions.map((status) => (
                                        <DropdownMenuItem
                                            key={status}
                                            variant={
                                                status === 'cancelled'
                                                    ? 'destructive'
                                                    : 'default'
                                            }
                                            onSelect={() =>
                                                onTransition(order, status)
                                            }
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
                                            <DropdownMenuItem
                                                variant="destructive"
                                                onSelect={() => onDelete(order)}
                                            >
                                                {t('Delete')}
                                            </DropdownMenuItem>
                                        </>
                                    )}
                                </>
                            )}
                        </DataTableRowActions>
                    </div>
                );
            },
        },
    ];
}
