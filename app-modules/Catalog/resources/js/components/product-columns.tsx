import {
    actionsColumn,
    dateColumn,
    sortableColumn,
    DeleteMenuItem,
    EditMenuItem,
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
        sortableColumn<ProductRow>({
            id: 'name',
            title: t('Name'),
            cell: (row) => <span className="font-medium">{row.name}</span>,
        }),
        sortableColumn<ProductRow>({
            id: 'sku',
            title: t('SKU'),
            cell: (row) => (
                <span className="font-mono text-sm text-muted-foreground">
                    {row.sku}
                </span>
            ),
        }),
        sortableColumn<ProductRow>({
            id: 'price_cents',
            title: t('Price'),
            cell: (row) => (
                <span className="tabular-nums">
                    {format.money(row.price_cents, row.currency)}
                </span>
            ),
        }),
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
        dateColumn<ProductRow>({
            id: 'created_at',
            title: t('Created'),
            value: (product) => product.created_at,
            format: format.date,
        }),
        actionsColumn<ProductRow>({
            label: t('Actions'),
            visible: (product) =>
                product.can.update || product.can.publish || product.can.delete,
            items: (product) => (
                <>
                    {product.can.update && (
                        <EditMenuItem href={edit(product.id)} />
                    )}
                    {product.can.publish && (
                        <DropdownMenuItem onSelect={() => onPublish(product)}>
                            {t('Publish')}
                        </DropdownMenuItem>
                    )}
                    {product.can.delete && (
                        <DeleteMenuItem onSelect={() => onDelete(product)} />
                    )}
                </>
            ),
        }),
    ];
}
