import { Link } from '@inertiajs/react';
import {
    DataTableColumnHeader,
    DataTableRowActions,
    type DataTableColumn,
} from '@/components/data-table';
import { Badge } from '@/components/ui/badge';
import { DropdownMenuItem } from '@/components/ui/dropdown-menu';
import type { Formatters } from '@/lib/format';
import { edit } from '@/routes/admin/products';
import type { Translator } from '@/types/ui';

export type ProductRow = Modules.Catalog.Data.ProductData;

export function buildProductColumns(
    t: Translator,
    format: Formatters,
    onPublish: (product: ProductRow) => void,
    onDelete: (product: ProductRow) => void,
): DataTableColumn<ProductRow>[] {
    return [
        {
            id: 'name',
            accessorKey: 'name',
            enableSorting: true,
            header: ({ column }) => (
                <DataTableColumnHeader column={column} title={t('Name')} />
            ),
            cell: ({ row }) => (
                <span className="font-medium">{row.original.name}</span>
            ),
        },
        {
            id: 'sku',
            accessorKey: 'sku',
            enableSorting: true,
            header: ({ column }) => (
                <DataTableColumnHeader column={column} title={t('SKU')} />
            ),
            cell: ({ row }) => (
                <span className="font-mono text-sm text-muted-foreground">
                    {row.original.sku}
                </span>
            ),
        },
        {
            id: 'price_cents',
            accessorKey: 'price_cents',
            enableSorting: true,
            header: ({ column }) => (
                <DataTableColumnHeader column={column} title={t('Price')} />
            ),
            cell: ({ row }) => (
                <span className="tabular-nums">
                    {format.money(
                        row.original.price_cents,
                        row.original.currency,
                    )}
                </span>
            ),
        },
        {
            id: 'status',
            enableSorting: false,
            header: t('Status'),
            cell: ({ row }) =>
                row.original.published_at ? (
                    <Badge>{t('Published')}</Badge>
                ) : (
                    <Badge variant="outline">{t('Draft')}</Badge>
                ),
        },
        {
            id: 'created_at',
            accessorKey: 'created_at',
            enableSorting: true,
            header: ({ column }) => (
                <DataTableColumnHeader column={column} title={t('Created')} />
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
                const product = row.original;

                if (
                    !product.can.update &&
                    !product.can.publish &&
                    !product.can.delete
                ) {
                    return null;
                }

                return (
                    <div className="flex justify-end">
                        <DataTableRowActions row={row}>
                            {() => (
                                <>
                                    {product.can.update && (
                                        <DropdownMenuItem asChild>
                                            <Link href={edit(product.id)}>
                                                {t('Edit')}
                                            </Link>
                                        </DropdownMenuItem>
                                    )}
                                    {product.can.publish && (
                                        <DropdownMenuItem
                                            onSelect={() => onPublish(product)}
                                        >
                                            {t('Publish')}
                                        </DropdownMenuItem>
                                    )}
                                    {product.can.delete && (
                                        <DropdownMenuItem
                                            variant="destructive"
                                            onSelect={() => onDelete(product)}
                                        >
                                            {t('Delete')}
                                        </DropdownMenuItem>
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
